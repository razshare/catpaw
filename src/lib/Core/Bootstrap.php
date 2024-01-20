<?php

namespace CatPaw\Core;

use function Amp\async;
use Amp\DeferredFuture;
use function Amp\delay;
use function Amp\File\isDirectory;

use CatPaw\Core\Services\EnvironmentService;

use Phar;
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
     * @param  array         $libraries
     * @return Unsafe<mixed>
     */
    public static function init(
        string $fileName,
        array $libraries = [],
    ):Unsafe {
        if (isPhar()) {
            $fileName = Phar::running()."/$fileName";
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
            Container::load(path:$path, append:true)->try($error);
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

            /** @var LoggerInterface $logger */
            $logger = LoggerFactory::create($name)->try($error);
            if ($error) {
                self::kill($error->getMessage());
            }

            Container::set(LoggerInterface::class, $logger);
            $environmentService = new EnvironmentService($logger);

            if ($environment) {
                if (File::exists($environment)) {
                    $environmentService->setFileName($environment);
                    $environmentService->load($info)->try($error);
                    if ($error) {
                        self::kill($error->getMessage());
                    }
                }
            }

            /** @var array<string> $librariesList */
            $librariesList = !$libraries ? [] : preg_split('/[,;]/', $libraries);

            /** @var array<string> $resourcesList */
            $resourcesList = !$resources ? [] : preg_split('/[,;]/', $resources);

            $_ENV['ENTRY']         = $entry;
            $_ENV['LIBRARIES']     = $librariesList;
            $_ENV['RESOURCES']     = $resourcesList;
            $_ENV['DIE_ON_CHANGE'] = $dieOnChange;
            $_ENV['SHOW_INFO']     = $info;

            foreach ($librariesList as $library) {
                if (!str_starts_with($library, './')) {
                    if (!$isWindows) {
                        self::kill("All library directory paths must be relative to the project, received: $library.");
                    }
                    if (!str_starts_with($library, '.\\')) {
                        self::kill("All library directory paths must be relative to the project, received: $library.");
                    }
                }
            }

            foreach ($resourcesList as $resource) {
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
                    libraries: $librariesList,
                    resources: $resourcesList,
                    function: static function() {
                        self::kill("Killing application...");
                    },
                );
            }

            self::init($entry, $librariesList)->try($error);

            if ($error) {
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
     * @param  callable():(void) $function
     * @return void
     */
    public static function onKill(callable $function): void {
        self::$onKillActions[] = $function;
    }

    /**
     * @param  string $message
     * @return never
     */
    public static function kill(string $message = ''):never {
        foreach (self::$onKillActions as $function) {
            $function();
        }
        echo $message.PHP_EOL;
        die(CommandStatus::SUCCESS);
    }

    /**
     * @param  string                      $binary
     * @param  string                      $fileName
     * @param  array                       $arguments
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
            if (!Container::has(LoggerInterface::class)) {
                $logger = LoggerFactory::create()->try($error);
                if ($error) {
                    return error($error);
                }
                Container::set(LoggerInterface::class, $logger);
            } else {
                $logger = Container::create(LoggerInterface::class)->try($error);
                if ($error) {
                    return error($error);
                }
            }

            $argumentsStringified = join(' ', $arguments);
            $instruction          = "$binary $fileName $argumentsStringified";

            echo "Spawning $instruction".PHP_EOL;

            $signal = Signal::create();

            /** @var array<string> $librariesList */
            $librariesList = !$libraries ? [] : preg_split('/[,;]/', $libraries);

            /** @var array<string> $resourcesList */
            $resourcesList = !$resources ? [] : preg_split('/[,;]/', $resources);

            if (DIRECTORY_SEPARATOR === '/') {
                EventLoop::onSignal(SIGINT, static function() use ($signal) {
                    $signal->sigterm();
                    self::kill();
                });
            }


            echo "RESOURCES:\n";

            print_r($resourcesList);

            echo PHP_EOL;


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

            while (true) {
                if ($ready) {
                    $ready->getFuture()->await();
                }
                execute($instruction, out())->await()->try($error);
                if ($error) {
                    echo $error.PHP_EOL;
                    $ready = new DeferredFuture;
                }
            }
        });

        EventLoop::run();
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

                    $flatList = Directory::flat(realpath($directory))->try($error);

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
