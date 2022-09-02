<?php

namespace CatPaw;

use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;

use function Amp\call;
use function Amp\delay;
use function Amp\File\createDefaultDriver;
use function Amp\File\exists;
use Amp\File\File;

use Amp\File\Filesystem;

use Amp\Loop;
use Amp\Process\Process;
use Amp\Promise;
use CatPaw\Attributes\Entry;
use CatPaw\Utilities\Container;
use CatPaw\Utilities\LoggerFactory;
use Generator;
use Psr\Log\LoggerInterface;
use ReflectionClass;

use ReflectionException;

use ReflectionFunction;
use ReflectionMethod;
use Throwable;

class Bootstrap {
    private function __contruct() {
    }

    /**
     * 
     * @param  class-string                                      $className
     * @throws ReflectionException
     * @return array{0:ReflectionClass,1:ReflectionMethod|false}
     */
    private static function entry(string $className): array|false {
        $i = new ReflectionClass($className);
        foreach ($i->getMethods() as $method) {
            if (($attributes = $method->getAttributes(Entry::class))) {
                /**
                 * @psalm-suppress RedundantConditionGivenDocblockType
                 */
                if (count($attributes) > 0) {
                    return [$i, $method];
                }
            }
        }
        return [$i, false];
    }

    /**
     * Initialize an application from a soruce file (that usually defines a global "main" function).
     * @param  string              $filename
     * @throws ReflectionException
     * @return Promise<void>
     */
    public static function init(string $filename): Promise {
        return call(function() use ($filename) {
            if (isPhar()) {
                $filename = \Phar::running()."/$filename";
            }
            if (yield exists($filename)) {
                /**
                 * @psalm-suppress UnresolvableInclude
                 */
                require_once $filename;
                /** @var mixed $result */
                if (!function_exists('main')) {
                    yield self::kill("Please define a global main function.\n");
                }
                /**
                 * @psalm-suppress InvalidArgument
                 */
                $main = new ReflectionFunction('main');
    
                foreach ($main->getAttributes() as $attribute) {
                    $attributeArguments = $attribute->getArguments();
                    $className          = $attribute->getName();
                    /**
                     * @psalm-suppress ArgumentTypeCoercion
                     */
                    [$klass, $entry] = self::entry($className);
                    $object          = $klass->newInstance(...$attributeArguments);
                    if ($entry) {
                        $arguments = yield Container::dependencies($entry);
                        yield \Amp\call(fn():mixed => $entry->invoke($object, ...$arguments));
                    }
                }
                
                yield Container::run($main);
            } else {
                yield self::kill("Could not find php entry file \"$filename\".\n");
            }
        });
    }

    /**
     * Bootstrap an application from a file.
     * @param  string    $entry       the entry file of the application (it usually defines a global "main" function)
     * @param  string    $name        application name (this will be used by the default logger)
     * @param  string    $libraries
     * @param  string    $resources
     * @param  bool      $info
     * @param  bool      $dieOnChange
     * @throws Throwable
     * @return void
     */
    public static function start(
        string $entry,
        string $name,
        string $libraries,
        string $resources,
        bool $info = false,
        bool $dieOnChange = false,
    ):void {
        Loop::run(function() use ($entry, $libraries, $info, $dieOnChange, $resources, $name) {
            if (!$entry) {
                yield self::kill("Please point to a php entry file.\n");
            }

            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            if (!str_starts_with($entry, './')) {
                if (!$isWindows) {
                    die("The entry file path must be relative to the project, received: $entry.".PHP_EOL);
                }
                if (!str_starts_with($entry, '.\\')) {
                    die("The entry file path must be relative to the project, received: $entry.".PHP_EOL);
                }
            }

            Container::set(LoggerInterface::class, LoggerFactory::create($name));
            /** @var array<string> */
            $libraries = !$libraries?[]:\preg_split('/,|;/', $libraries);
            /** @var array<string> */
            $resources = !$resources?[]:\preg_split('/,|;/', $resources);


            $_ENV['ENTRY']         = $entry;
            $_ENV['LIBRARIES']     = $libraries;
            $_ENV['RESOURCES']     = $resources;
            $_ENV['DIE_ON_CHANGE'] = $dieOnChange;
            $_ENV['SHOW_INFO']     = $info;

            foreach ($libraries as $library) {
                if (!str_starts_with($library, './')) {
                    if (!$isWindows) {
                        yield self::kill("All library directory paths must be relative to the project, received: $library.".PHP_EOL);
                    }
                    if (!str_starts_with($library, '.\\')) {
                        yield self::kill("All library directory paths must be relative to the project, received: $library.".PHP_EOL);
                    }
                }
            }

            foreach ($resources as $resource) {
                if (!str_starts_with($resource, './')) {
                    if (!$isWindows) {
                        yield self::kill("All resource directory paths must be relative to the project, received: $resource.".PHP_EOL);
                    }
                    if (!str_starts_with($resource, '.\\')) {
                        yield self::kill("All resource directory paths must be relative to the project, received: $resource.".PHP_EOL);
                    }
                }
            }

            if ($dieOnChange) {
                if (isPhar()) {
                    /** @var LoggerInterface */
                    $logger = yield Container::create(LoggerInterface::class);
                    $logger->error("Watch mode is intended for development only, compiled phar applications cannot watch files for changes.");
                    yield self::kill();
                }
                self::dieOnChange(
                    entry: $entry,
                    libraries: $libraries,
                    resources: $resources,
                );
            }
            try {
                yield Container::load($libraries);
                
                if ($info) {
                    echo Container::describe();
                }
                yield self::init($entry);
            } catch (Throwable $e) {
                if ($dieOnChange) {
                    echo $e.\PHP_EOL;
                    while (true) {
                        yield delay(1000);
                    }
                }
                throw $e;
            }
        });
    }

    /** @var array<callable():(void|Generator|Promise)> */
    private static array $onKillActions = [];

    /**
     * Execute something when the application get killed through Bootstrap::kill.
     * @param  callable():(void|Generator|Promise) $callback
     * @return void
     */
    public static function onKill(callable $callback) {
        self::$onKillActions[] = $callback;
    }

    public static function kill(string $message = ''):Promise {
        return call(function() use ($message) {
            foreach (self::$onKillActions as $callback) {
                yield call($callback);
            }
            die($message);
        });
    }
    
    /**
     * @param string $start
     * @param string $entry
     * @param string $name
     * @param  string    libraries
     * @param  string    $resources
     * @param  bool      $info
     * @param  bool      $watch
     * @throws Throwable
     * @return void
     */
    public static function spawn(
        string $start,
        string $entry,
        string $name,
        string $libraries,
        string $resources,
        bool $info = false,
        bool $watch = false,
    ) {
        Loop::run(function() use (
            $start,
            $entry,
            $name,
            $libraries,
            $info,
            $watch,
            $resources,
        ) {
            $out = new ResourceOutputStream(STDOUT);
            $err = new ResourceOutputStream(STDERR);
            $in  = new ResourceInputStream(STDIN);

            $options = [ "-e\"$entry\"" ];

            if ($name) {
                $options[] = "-n\"$name\"";
            }
            if ($libraries) {
                $options[] = "-l\"$libraries\"";
            }
            if ($resources) {
                $options[] = "-r\"$resources\"";
            }
            if ($watch) {
                $options[] = '-d';
            }
            if ($info) {
                $options[] = '-i';
            }

            $params = join(' ', $options);
            while (true) {
                echo "Spawning $start $params".PHP_EOL;
                $process = new Process("$start $params");

                yield $process->start();

                $pout = $process->getStdout();
                $perr = $process->getStderr();
                $pin  = $process->getStdin();

                call(function() use ($pout, $out) {
                    while ($chunk = yield $pout->read()) {
                        yield $out->write($chunk);
                    }
                });

                call(function() use ($perr, $err) {
                    while ($chunk = yield $perr->read()) {
                        yield $err->write($chunk);
                    }
                });

                call(function() use ($pin, $in) {
                    while ($chunk = yield $in->read()) {
                        yield $pin->write($chunk);
                    }
                });
                try {
                    $code = yield $process->join();
                    yield delay(1000);
                } catch (Throwable $e) {
                    echo join("\n", [
                        $e->getMessage(),
                        $e->getTraceAsString()
                    ]).PHP_EOL;
                    yield delay(1000 * 5);
                }
            }
        });
    }

    /**
     * Start a watcher which will kill the application when any observed file changes.
     * Useful for development mode.
     * @param  string        $entry
     * @param  array         $libraries
     * @param  array         $resources
     * @return Promise<void>
     */
    private static function dieOnChange(
        string $entry,
        array $libraries,
        array $resources,
    ):Promise {
        return call(function() use ($entry, $libraries, $resources) {
            $fs        = new Filesystem(createDefaultDriver());
            $changes   = [];
            $firstPass = true;

            while (true) {
                clearstatcache();
                $countLastPass = count($changes);

                $filenames = [ $entry => false ];
                foreach ([...$libraries,...$resources] as $directory) {
                    if (!yield exists($directory)) {
                        continue;
                    }
                    foreach (yield listFilesRecursively(\realpath($directory)) as $i => $filename) {
                        $filenames[$filename] = false;
                    }
                }

                
                $countThisPass = count($filenames);
                if (!$firstPass && $countLastPass !== $countThisPass) {
                    yield self::kill("Killing application...");
                }

                foreach (array_keys($filenames) as $filename) {
                    if (!yield exists($filename)) {
                        $changes[$filename] = 0;
                        continue;
                    }
                    $mtime = yield $fs->getModificationTime($filename);
                    if (!isset($changes[$filename])) {
                        $changes[$filename] = $mtime;
                        continue;
                    }
                    
                    if ($changes[$filename] !== $mtime) {
                        yield self::kill("Killing application...");
                    }
                }

                $firstPass = false;
                yield delay(1000);
            }
        });
    }
}
