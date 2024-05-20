<?php

namespace CatPaw\Core;

use function Amp\async;
use Amp\DeferredFuture;
use function Amp\delay;
use function Amp\File\isDirectory;

use CatPaw\Core\Services\EnvironmentService;
use Error;
use function preg_split;
use Psr\Log\LoggerInterface;
use function realpath;
use ReflectionFunction;
use Revolt\EventLoop;
use Revolt\EventLoop\UnsupportedFeatureException;
use Throwable;

class Bootstrap {
    private function __construct() {
    }

    /**
     * Initialize an application from a source file (that usually defines a global "main" function).
     * @param  string        $fileName
     * @param  array<string> $libraries
     * @return Unsafe<mixed>
     */
    public static function init(
        string $fileName,
        array $libraries = [],
    ):Unsafe {
        if (!File::exists($fileName)) {
            return error("Could not find php entry file $fileName.");
        }

        require_once $fileName;

        if (!function_exists('main')) {
            return error("could not find a global main function.\n");
        }

        $main = new ReflectionFunction('main');

        Container::touch($main);

        foreach ($libraries as $path) {
            Container::load(path:$path, append:true)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        return Container::run($main, false);
    }

    /**
     * Bootstrap an application from a file.
     * @param  string $entry       the entry file of the application (it usually defines a global "main" function)
     * @param  string $name        application name (this will be used by the default logger)
     * @param  string $libraries   libraries to load
     * @param  string $resources   resources to load
     * @param  string $environment
     * @param  bool   $dieOnChange die when a change to the entry file, libraries or resources is detected
     * @return void
     */
    public static function start(
        string $entry,
        string $name,
        string $libraries,
        string $resources,
        string $environment,
        bool $dieOnChange = false,
    ): void {
        try {
            $entryLocal = (string)asFileName($entry);
            if ('' === $entryLocal) {
                self::kill("Please point to a valid php entry file, received `$entry`.");
            }
            $entry = $entryLocal;

            /** @var LoggerInterface $logger */
            $logger = LoggerFactory::create($name)->unwrap($error);
            if ($error) {
                self::kill((string)$error);
            }

            Container::provide(LoggerInterface::class, $logger);
            /** @var array<string> $librariesList */
            $librariesList = !$libraries ? [] : preg_split('/[,;]/', $libraries);

            /** @var array<string> $resourcesList */
            $resourcesList = !$resources ? [] : preg_split('/[,;]/', $resources);

            $env = new EnvironmentService($logger);
            Container::provide(EnvironmentService::class, $env);

            $env->set('ENTRY', $entry);
            $env->set('LIBRARIES', $librariesList);
            $env->set('RESOURCES', $resourcesList);
            $env->set('DIE_ON_CHANGE', $dieOnChange);

            if ($environment) {
                $env->setFileName($environment);
                $env->load()->unwrap($error);
                if ($error) {
                    self::kill((string)$error);
                }
            }

            foreach ($librariesList as &$library) {
                $libraryLocal = (string)asFileName($library);
                if ('' === $libraryLocal) {
                    self::kill("Trying to load php library `$library`, but the directory doesn't seem to exist.");
                }
                $library = $libraryLocal;
            }

            foreach ($resourcesList as $resource) {
                $resourceLocal = (string)asFileName($resource);
                if ('' === $resourceLocal) {
                    self::kill("Trying to track resource `$resource`, but it doesn't seem to exist.");
                }
                $resource = $resourceLocal;
            }

            if ($dieOnChange) {
                if (isPhar()) {
                    self::kill("Watch mode is intended for development only, compiled phar applications cannot watch files for changes.");
                }
                self::onFileChange(
                    entry: $entry,
                    libraries: $librariesList,
                    resources: $resourcesList,
                    function: static function() {
                        self::kill("Killing application...", 0);
                    },
                );
            }

            self::init($entry, $librariesList)->unwrap($error);

            if ($error) {
                self::kill((string)$error);
            }

            EventLoop::run();
        } catch (Throwable $error) {
            self::kill((string)$error);
        }
    }

    /** @var array<callable():(void)> */
    private static array $onKillActions = [];

    /**
     * Execute something when the application get killed through Bootstrap::kill.
     * @param  callable():(void) $function
     * @return void
     */
    public static function onKill(callable $function): void {
        self::$onKillActions[] = $function;
    }

    /**
     * @param  false|string|Error $error
     * @return never
     */
    public static function kill(false|string|Error $error = false, false|int $code = false):never {
        foreach (self::$onKillActions as $function) {
            $function();
        }

        if ($error) {
            echo $error.PHP_EOL;
            if (false === $code) {
                die(CommandStatus::OPERATION_CANCELED);
            } else {
                die($code);
            }
        }

        if (false === $code) {
            die(CommandStatus::SUCCESS);
        } else {
            die($code);
        }
    }

    /**
     * @param  string                      $binary
     * @param  string                      $fileName
     * @param  array<string>               $arguments
     * @param  string                      $entry
     * @param  string                      $libraries
     * @param  string                      $resources
     * @throws UnsupportedFeatureException
     * @return void
     */
    public static function spawn(
        string $binary,
        string $fileName,
        array $arguments,
        string $entry,
        string $libraries,
        string $resources,
    ):void {
        try {
            EventLoop::onSignal(SIGHUP, static fn () => self::kill("Killing application..."));
            EventLoop::onSignal(SIGINT, static fn () => self::kill("Killing application..."));
            EventLoop::onSignal(SIGQUIT, static fn () => self::kill("Killing application..."));
            EventLoop::onSignal(SIGTERM, static fn () => self::kill("Killing application..."));

            async(static function() use (
                $binary,
                $fileName,
                $arguments,
                $entry,
                $libraries,
                $resources,
            ) {
                if (!Container::isProvided(LoggerInterface::class)) {
                    $logger = LoggerFactory::create()->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    Container::provide(LoggerInterface::class, $logger);
                } else {
                    $logger = Container::create(LoggerInterface::class)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                }

                $argumentsStringified = join(' ', $arguments);
                $instruction          = "$binary $fileName $argumentsStringified";

                echo "Spawning $instruction".PHP_EOL;

                /** @var array<string> $librariesList */
                $librariesList = !$libraries ? [] : preg_split('/[,;]/', $libraries);

                /** @var array<string> $resourcesList */
                $resourcesList = !$resources ? [] : preg_split('/[,;]/', $resources);

                if (DIRECTORY_SEPARATOR === '/') {
                    EventLoop::onSignal(SIGINT, static function() {
                        self::kill();
                    });
                }

                /** @var false|DeferredFuture<void> $ready */
                $ready = false;

                self::onFileChange(
                    entry: $entry,
                    libraries: $librariesList,
                    resources: $resourcesList,
                    function: static function() use (&$ready) {
                        if (!$ready) {
                            return;
                        }
                        $ready->complete();
                    },
                );

                // @phpstan-ignore-next-line
                while (true) {
                    if ($ready) {
                        $ready->getFuture()->await();
                    }
                    $code = execute($instruction, out())->unwrap($error);
                    if ($error || $code > 0) {
                        echo $error.PHP_EOL;
                        $ready = new DeferredFuture;
                    }
                }
            });

            EventLoop::run();
        } catch (Throwable $error) {
            self::kill($error);
        }
    }

    /**
     * Start a watcher which will detect file changes.
     * Useful for development mode.
     * @param  string        $entry
     * @param  array<string> $libraries
     * @param  array<string> $resources
     * @param  callable      $function
     * @return void
     */
    private static function onFileChange(
        string $entry,
        array $libraries,
        array $resources,
        callable $function,
    ): void {
        async(function() use (
            $entry,
            $libraries,
            $resources,
            $function,
        ) {
            $changes   = [];
            $firstPass = true;

            while (true) {
                clearstatcache();
                $countLastPass = count($changes);

                $fileNames = [$entry => false];
                /** @var array<string> $files */
                $files = [...$libraries, ...$resources];

                foreach ($files as $file) {
                    if (!File::exists($file)) {
                        continue;
                    }

                    if (!isDirectory($file)) {
                        $fileNames[$file] = false;
                        continue;
                    }

                    $directory = $file;

                    $flatList = Directory::flat(realpath($directory))->unwrap($error);

                    if ($error) {
                        return error($error);
                    }

                    foreach ($flatList as $fileName) {
                        $fileNames[$fileName] = false;
                    }
                }


                $countThisPass = count($fileNames);
                if (!$firstPass && $countLastPass !== $countThisPass) {
                    $function();
                }

                foreach (array_keys($fileNames) as $fileName) {
                    if (!File::exists($fileName)) {
                        unset($changes[$fileName]);
                        continue;
                    }

                    $mtime = filemtime($fileName);

                    if (false === $mtime) {
                        return error("Could not read file $fileName modification time.");
                    }

                    if (!isset($changes[$fileName])) {
                        $changes[$fileName] = $mtime;
                        continue;
                    }

                    if ($changes[$fileName] !== $mtime) {
                        $changes[$fileName] = $mtime;
                        $function();
                    }
                }

                $firstPass = false;
                delay(0.1);
            }
        });
    }
}
