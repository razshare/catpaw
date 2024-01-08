<?php

namespace CatPaw;

use function Amp\async;
use Amp\DeferredFuture;

use function Amp\delay;
use CatPaw\Services\EnvironmentService;
use Psr\Log\LoggerInterface;
use ReflectionException;
use ReflectionFunction;
use Revolt\EventLoop;
use Throwable;

class Bootstrap {
    private function __contruct() {
    }

    /**
     * Initialize an application from a soruce file (that usually defines a global "main" function).
     * @param  string              $fileName
     * @param  bool                $info
     * @throws ReflectionException
     * @return Unsafe<mixed>
     */
    public static function init(
        string $fileName,
        array $libraries = [],
        bool $info = false,
    ):Unsafe {
        if (isPhar()) {
            $fileName = \Phar::running()."/$fileName";
        }

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
            if ($error = Container::load(path:$path, append:true)->error) {
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
     * @param  bool   $info        if true, the bootstrap starter will write feedback messages to stdout, otherwise it will be silent unless it crashes with an exception.
     * @param  bool   $dieOnChange die when a change to the entry file, libraries or resources is detected
     * @return void
     */
    public static function start(
        string $entry,
        string $name,
        string $libraries,
        string $resources,
        string $environment,
        bool $info = false,
        bool $dieOnChange = false,
    ): void {
        try {
            if (!$entry) {
                self::kill("Please point to a php entry file.");
            }
    
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
            if (!str_starts_with($entry, './')) {
                if (!$isWindows) {
                    self::kill("The entry file path must be relative to the project, received: $entry.");
                }
                if (!str_starts_with($entry, '.\\')) {
                    self::kill("The entry file path must be relative to the project, received: $entry.");
                }
            }
    
            $loggerAttempt = LoggerFactory::create($name);
            if ($loggerAttempt->error) {
                self::kill($loggerAttempt->error->getMessage());
            }

            $logger = $loggerAttempt->value;
            Container::set(LoggerInterface::class, $logger);
            $environmentService = new EnvironmentService($logger);

            if ($environment) {
                if (File::exists($environment)) {
                    $environmentService->setFileName($environment);
                    if ($error = $environmentService->load($info)->error) {
                        self::kill($error->getMessage());
                    }
                }
            }

            /** @var array<string> */
            $libraries = !$libraries ? [] : \preg_split('/,|;/', $libraries);
            /** @var array<string> */
            $resources = !$resources ? [] : \preg_split('/,|;/', $resources);
    
            $_ENV['ENTRY']         = $entry;
            $_ENV['LIBRARIES']     = $libraries;
            $_ENV['RESOURCES']     = $resources;
            $_ENV['DIE_ON_CHANGE'] = $dieOnChange;
            $_ENV['SHOW_INFO']     = $info;
    
            foreach ($libraries as $library) {
                if (!str_starts_with($library, './')) {
                    if (!$isWindows) {
                        self::kill("All library directory paths must be relative to the project, received: $library.");
                    }
                    if (!str_starts_with($library, '.\\')) {
                        self::kill("All library directory paths must be relative to the project, received: $library.");
                    }
                }
            }
    
            foreach ($resources as $resource) {
                if (!str_starts_with($resource, './')) {
                    if (!$isWindows) {
                        self::kill("All resource directory paths must be relative to the project, received: $resource.");
                    }
                    if (!str_starts_with($resource, '.\\')) {
                        self::kill("All resource directory paths must be relative to the project, received: $resource.");
                    }
                }
            }
    
            if ($dieOnChange) {
                if (isPhar()) {
                    self::kill("Watch mode is intended for development only, compiled phar applications cannot watch files for changes.");
                }
                self::onFileChange(
                    entry: $entry,
                    libraries: $libraries,
                    resources: $resources,
                    callback: static function() {
                        self::kill("Killing application...");
                    },
                );
            }

            if ($error = self::init($entry, $libraries, $info)->error) {
                self::kill((string)$error);
            }

            EventLoop::run();
        } catch (Throwable $e) {
            self::kill((string)$e);
        }
    }

    /** @var array<callable():(void)> */
    private static array $onKillActions = [];

    /**
     * Execute something when the application get killed through Bootstrap::kill.
     * @param  callable():(void) $callback
     * @return void
     */
    public static function onKill(callable $callback) {
        self::$onKillActions[] = $callback;
    }

    public static function kill(string $message = ''):never {
        foreach (self::$onKillActions as $callback) {
            $callback();
        }
        echo $message.PHP_EOL;
        die(CommandStatus::SUCCESS);
    }

    /**
     * @param  string    $binary
     * @param  array     $arguments
     * @param  string    $start
     * @throws Throwable
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
        EventLoop::onSignal(SIGHUP, static fn () => self::kill("Killing application..."));
        EventLoop::onSignal(SIGINT, static fn () => self::kill("Killing application..."));
        EventLoop::onSignal(SIGQUIT, static fn () => self::kill("Killing application..."));
        EventLoop::onSignal(SIGTERM, static fn () => self::kill("Killing application..."));

        async( static function() use (
            $binary,
            $fileName,
            $arguments,
            $entry,
            $libraries,
            $resources,
        ) {
            $argumentsStringified = join(' ', $arguments);
            $instruction          = "$binary $fileName $argumentsStringified";

            echo "Spawning $instruction".PHP_EOL;

            $kill = Signal::create();
        
            /** @var array<string> */
            $libraries = !$libraries ? [] : \preg_split('/,|;/', $libraries);
            /** @var array<string> */
            $resources = !$resources ? [] : \preg_split('/,|;/', $resources);

            if (DIRECTORY_SEPARATOR === '/') {
                EventLoop::onSignal(SIGINT, static function() use ($kill) {
                    $kill->send();
                    self::kill();
                });
            }


            /** @var false|DeferredFuture<void> */
            $ready = false;

            self::onFileChange(
                entry: $entry,
                libraries: $libraries,
                resources: $resources,
                callback: static function() use (&$ready) {
                    if (!$ready) {
                        return;
                    }
                    $ready->complete();
                },
            );

            while (true) {
                if ($ready) {
                    $ready->getFuture()->await();
                }
                if ($error = execute($instruction, out(), $kill)->await()->error) {
                    echo (string)$error.PHP_EOL;
                    $ready = new DeferredFuture;
                }
            }
        });

        EventLoop::run();
    }

    /**
     * Start a watcher which will detect file changes.
     * Useful for development mode.
     * @param  string $entry
     * @param  array  $libraries
     * @param  array  $resources
     * @return void
     */
    private static function onFileChange(
        string $entry,
        array $libraries,
        array $resources,
        callable $callback,
    ) {
        async(function() use (
            $entry,
            $libraries,
            $resources,
            $callback,
        ) {
            $changes   = [];
            $firstPass = true;

            while (true) {
                clearstatcache();
                $countLastPass = count($changes);

                $fileNames = [$entry => false];
                foreach ([...$libraries, ...$resources] as $directory) {
                    if (!File::exists($directory)) {
                        continue;
                    }
                    $listAttempt = Directory::flat(\realpath($directory));

                    if ($listAttempt->error) {
                        return error($listAttempt->error);
                    }

                    foreach ($listAttempt->value as $fileName) {
                        $fileNames[$fileName] = false;
                    }
                }


                $countThisPass = count($fileNames);
                if (!$firstPass && $countLastPass !== $countThisPass) {
                    $callback();
                }

                foreach (array_keys($fileNames) as $fileName) {
                    if (!File::exists($fileName)) {
                        unset($changes[$fileName]);
                        continue;
                    }

                    $mtimeAttempt = File::getModificationTime($fileName);

                    if ($mtimeAttempt->error) {
                        return error($mtimeAttempt->error);
                    }

                    $mtime = $mtimeAttempt->value;

                    if (!isset($changes[$fileName])) {
                        $changes[$fileName] = $mtime;
                        continue;
                    }

                    if ($changes[$fileName] !== $mtime) {
                        $changes[$fileName] = $mtime;
                        $callback();
                    }
                }

                $firstPass = false;
                delay(1);
            }
            return ok();
        });
    }
}
