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

use Throwable;

class Bootstrap {
    /**
     * @throws ReflectionException
     */
    private static function entry(string $className): array|false {
        $i = new ReflectionClass($className);
        foreach ($i->getMethods() as $method) {
            if (($attributes = $method->getAttributes(Entry::class))) {
                if (count($attributes) > 0) {
                    return [$i, $method];
                }
            }
        }
        return [$i, false];
    }

    /**
     * @param  string              $filename
     * @throws ReflectionException
     * @return Generator
     */
    private static function init(string $filename): Generator {
        if (yield exists($filename)) {
            $filename = realpath($filename);
            $owd = \getcwd();
            chdir(dirname($filename));
            require_once $filename;
            chdir($owd);
            /** @var mixed $result */
            if (!function_exists('main')) {
                die("Please define a global main function.\n");
            }
            $main = new ReflectionFunction('main');

            foreach ($main->getAttributes() as $attribute) {
                $attributeArguments = $attribute->getArguments();
                $className          = $attribute->getName();
                /** @var ReflectionFunction $entry */
                [$klass, $entry] = self::entry($className);
                $object          = $klass->newInstance(...$attributeArguments);
                if ($entry) {
                    $arguments = yield Container::dependencies($entry);
                    yield \Amp\call(fn() => $entry->invoke($object, ...$arguments));
                }
            }
            yield Container::run($main);
        } else {
            die("Could not find php entry file \"$filename\".\n");
        }
    }

    /**
     * Bootstrap an application from a file.
     * @param  string    $name   application name (this will be used by the default logger)
     * @param  string    $file   php file to run (absolute path)
     * @param  string    $info   if true, will log all singletons loaded at startup
     * @param  string    $watch
     * @param  mixed     $onkill
     * @throws Throwable
     */
    public static function start(
        string $entry,
        string $name,
        string $library,
        string $resources,
        bool $info = false,
        bool $dieOnChange = false,
    ) {
        set_time_limit(0);
        ob_implicit_flush();
        ini_set('memory_limit', '-1');

        Container::setObject(LoggerInterface::class, LoggerFactory::create($name));

        if (!$entry) {
            die("Please point to a php entry file.\n");
        }

        Loop::run(function() use ($entry, $library, $info, $dieOnChange, $resources) {
            /** @var array<string> */
            $directories = !$library?[]:\preg_split('/,|;/', $library);
            /** @var array<string> */
            $resources   = !$resources?[]:\preg_split('/,|;/', $resources);

            foreach ($directories as $library) {
                if (!str_starts_with($library, '.'.DIRECTORY_SEPARATOR)) {
                    die("All library directory paths must be relative to the project, received: $library.".PHP_EOL);
                }
            }

            foreach ($resources as $resource) {
                if (!str_starts_with($resource, '.'.DIRECTORY_SEPARATOR)) {
                    die("All resource directory paths must be relative to the project, received: $resource.".PHP_EOL);
                }
            }

            if ($dieOnChange) {
                self::dieOnChange(
                    entry: $entry,
                    directories: $directories,
                    resources: $resources,
                    info: $info,
                );
            }
            try {
                yield Container::load($directories);
                
                if ($info) {
                    echo Container::describe();
                }
                yield from self::init($entry);
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

    
    private static array $onKillActions = [];

    public static function onKill($callback) {
        self::$onKillActions[] = $callback;
    }

    public static function kill():Promise {
        return call(function() {
            foreach (self::$onKillActions as $callback) {
                yield call($callback);
            }
            die();
        });
    }
    
    /**
     * @param  string    $start
     * @param  string    $entry
     * @param  string    $name
     * @param  string    $library
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
        string $library,
        string $resources,
        bool $info = false,
        bool $watch = false,
    ) {
        Loop::run(function() use (
            $start,
            $entry,
            $name,
            $library,
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
            if ($library) {
                $options[] = "-l\"$library\"";
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

    private static function dieOnChange(
        string $entry,
        array $directories,
        array $resources,
        bool $info = false,
    ):Promise {
        return call(function() use ($entry, $directories, $resources, $info) {
            $fs        = new Filesystem(createDefaultDriver());
            $changes   = [];
            $firstPass = true;
            /** @var LoggerInterface $logger */
            $logger = yield Container::create(LoggerInterface::class);

            while (true) {
                clearstatcache();
                $countLastPass = count($changes);

                $filenames = [ $entry ];
                foreach ($directories as $directory) {
                    $filenames = [...$filenames, ...(yield listFilesRecursively(\realpath($directory)))];
                }

                $countThisPass = count([...$filenames, ...$resources]);
                if (!$firstPass && $countLastPass !== $countThisPass) {
                    $logger->info("Killing application...");
                    yield self::kill();
                }

                foreach ($filenames as $filename) {
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
                        $logger->info("Killing application...");
                        yield self::kill();
                    }
                }

                foreach ($resources as $filename) {
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
                        $logger->info("Killing application...");
                        yield self::kill();
                    }
                }

                $firstPass = false;
                yield delay(1000);
            }
        });
    }
}
