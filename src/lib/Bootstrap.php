<?php

namespace CatPaw;

use function Amp\async;
use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\WritableResourceStream;

use function Amp\delay;
use function Amp\File\createDefaultDriver;
use function Amp\File\exists;
use Amp\File\Filesystem;

use function Amp\Future\await;
use Amp\Process\Process;
use CatPaw\Utilities\Container;
use CatPaw\Utilities\LoggerFactory;
use Exception;
use Generator;
use Psr\Log\LoggerInterface;

use ReflectionException;

use ReflectionFunction;
use Throwable;

class Bootstrap {
    private function __contruct() {
    }

    /**
     * Initialize an application from a soruce file (that usually defines a global "main" function).
     * @param  string              $filename
     * @param  bool                $info
     * @throws ReflectionException
     * @return Promise<void>
     */
    public static function init(
        string $filename,
        array $libraries = [],
        bool $info = false,
    ): Promise {
        return call(function() use ($filename, $libraries, $info) {
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

                yield Container::touch($main);

                yield Container::load(
                    locations: $libraries,
                    // This will maintain any singletons 
                    // set by "require_once $filename" 
                    // (instead of clearing all singletons before 
                    // loading new ones)
                    append: true
                );
            
                if ($info) {
                    echo Container::describe();
                }

                yield Container::run($main, false);
            } else {
                yield self::kill("Could not find php entry file \"$filename\".\n");
            }
        });
    }

    /**
     * Bootstrap an application from a file.
     * @param  string    $entry       the entry file of the application (it usually defines a global "main" function)
     * @param  string    $name        application name (this will be used by the default logger)
     * @param  string    $libraries   libraries to load
     * @param  string    $resources   resources to load
     * @param  bool      $info
     * @param  bool      $dieOnChange die when a change to the entry file, libraries or resources is detected
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
        try {
            Loop::run(function() use (
                $entry,
                $libraries,
                $info,
                $dieOnChange,
                $resources,
                $name
            ) {
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
                    self::onFileChange(
                        entry: $entry,
                        libraries: $libraries,
                        resources: $resources,
                        callback: static fn ():never => self::kill("Killing application..."),
                    );
                }

                yield self::init($entry, $libraries, $info);
            });
        } catch(Throwable $e) {
            echo $e.PHP_EOL;
            Loop::stop();
            exit(1);
        }
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

    public static function kill(string $message = ''):never {
        foreach (self::$onKillActions as $callback) {
            $callback();
        }
        die($message);
    }
    
    /**
     * @param  string    $binary
     * @param  string    $start
     * @param  array     $arguments
     * @throws Throwable
     * @return void
     */
    public static function spawn(
        string $binary,
        string $fileName,
        array $arguments = [],
        string $entry,
        string $libraries,
        string $resources,
    ):void {
        async(function() use (
            $binary,
            $fileName,
            $arguments,
            $entry,
            $libraries,
            $resources,
        ) {
            $out = new ResourceOutputStream(STDOUT);
            $err = new ResourceOutputStream(STDERR);
            $in  = new ResourceInputStream(STDIN);            
            
            $argumentsStringified = join(' ', $arguments);
            
            /** @var array<string> */
            $libraries = !$libraries?[]:\preg_split('/,|;/', $libraries);
            /** @var array<string> */
            $resources = !$resources?[]:\preg_split('/,|;/', $resources);
            
            $crashed = false;

            self::onFileChange(
                entry: $entry,
                libraries: $libraries,
                resources: $resources,
                callback: function() use (&$crashed) {
                    $crashed = false;
                },
            );

            /** @var false|Process */
            $process = false;

            if (DIRECTORY_SEPARATOR === '/') {
                Loop::onSignal(\SIGINT, static function(string $watcherId) use (&$process) {
                    $process->kill();
                    Loop::cancel($watcherId);
                    Loop::stop();
                });
            }

            while (true) {
                if ($crashed || ($process && $process->isRunning()) ) {
                    yield delay(100);
                    continue;
                }
                echo "Spawning $binary $fileName $argumentsStringified".PHP_EOL;
                $process = new Process("$binary $fileName $argumentsStringified");

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
                    /** @var int */
                    $code = yield $process->join();
                    if (0 !== $code) {
                        throw new Exception("Exiting with code $code");
                    }
                    yield delay(100);
                } catch (Throwable $e) {
                    $crashed = true;
                }
            }
        });
    }

    /**
     * Start a watcher which will detect file changes.
     * Useful for development mode.
     * @param  string        $entry
     * @param  array         $libraries
     * @param  array         $resources
     * @return Promise<void>
     */
    private static function onFileChange(
        string $entry,
        array $libraries,
        array $resources,
        callable $callback,
    ):Promise {
        return call(function() use (
            $entry,
            $libraries,
            $resources,
            $callback
        ) {
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
                    yield call($callback);
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
                        $changes[$filename] = $mtime;
                        yield call($callback);
                    }
                }

                $firstPass = false;
                yield delay(1000);
            }
        });
    }
}
