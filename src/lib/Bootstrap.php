<?php

namespace CatPaw;

use function Amp\async;

use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\WritableResourceStream;

use function Amp\delay;
use function Amp\File\createDefaultDriver;
use function Amp\File\exists;
use Amp\File\Filesystem;

use function Amp\Future\awaitAll;

use Amp\Process\Process;
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
     * @return void
     */
    public static function init(
        string $fileName,
        array $libraries = [],
        bool $info = false,
    ) {
        if (isPhar()) {
            $fileName = \Phar::running()."/$fileName";
        }

        $fileExists = exists($fileName);
        // $fileExists = true;

        if ($fileExists) {
            /**
             * @psalm-suppress UnresolvableInclude
             */
            require_once $fileName;
            /** @var mixed $result */
            if (!function_exists('main')) {
                self::kill("Please define a global main function.\n");
            }

            /**
             * @psalm-suppress InvalidArgument
             */
            $main = new ReflectionFunction('main');

            Container::touch($main);

            foreach ($libraries as $path) {
                Container::load(
                    path: $path,
                    append: true
                );
            }

            Container::run($main, false);
        } else {
            self::kill("Could not find php entry file \"$fileName\".\n");
        }
    }

    private const PATTERN_ENVIRONMENT_FILE_NAMES = '/(".+")|(\'.+\')|([^\s]+)/m';
    /**
     * Bootstrap an application from a file.
     * @param  string    $entry       the entry file of the application (it usually defines a global "main" function)
     * @param  string    $name        application name (this will be used by the default logger)
     * @param  string    $libraries   libraries to load
     * @param  string    $resources   resources to load
     * @param  bool      $info        if true, the bootstrap starter will write feedback messages to stdout, otherwise it will be silent unless it crashes with an exception.
     * @param  bool      $dieOnChange die when a change to the entry file, libraries or resources is detected
     * @throws Throwable
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
                self::kill("Please point to a php entry file.\n");
            }
    
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    
            if (!str_starts_with($entry, './')) {
                if (!$isWindows) {
                    self::kill("The entry file path must be relative to the project, received: $entry.".PHP_EOL);
                }
                if (!str_starts_with($entry, '.\\')) {
                    self::kill("The entry file path must be relative to the project, received: $entry.".PHP_EOL);
                }
            }
    
            $logger = LoggerFactory::create($name);
            Container::set(LoggerInterface::class, $logger);

            $environmentService = new EnvironmentService($logger);


            if ($environment) {
                $environmentService->setFiles([$environment]);
                $environmentService->load($info);
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
                        self::kill("All library directory paths must be relative to the project, received: $library.".PHP_EOL);
                    }
                    if (!str_starts_with($library, '.\\')) {
                        self::kill("All library directory paths must be relative to the project, received: $library.".PHP_EOL);
                    }
                }
            }
    
            foreach ($resources as $resource) {
                if (!str_starts_with($resource, './')) {
                    if (!$isWindows) {
                        self::kill("All resource directory paths must be relative to the project, received: $resource.".PHP_EOL);
                    }
                    if (!str_starts_with($resource, '.\\')) {
                        self::kill("All resource directory paths must be relative to the project, received: $resource.".PHP_EOL);
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
                    callback: function() {
                        self::kill("Application killed.\n");
                    },
                );
            }
            async(self::init(...), $entry, $libraries, $info)->await();
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
            $argumentsStringified = join(' ', $arguments);
            $instruction          = "$binary $fileName $argumentsStringified";
            $change               = false;
            $stdout               = new WritableResourceStream(STDOUT);
            $stderr               = new WritableResourceStream(STDERR);
            $stdin                = new ReadableResourceStream(STDIN);

            echo "Spawning $instruction".PHP_EOL;
            $process = Process::start($instruction);
        
            /** @var array<string> */
            $libraries = !$libraries ? [] : \preg_split('/,|;/', $libraries);
            /** @var array<string> */
            $resources = !$resources ? [] : \preg_split('/,|;/', $resources);

            if (DIRECTORY_SEPARATOR === '/') {
                EventLoop::onSignal(\SIGINT, static function() use (&$process) {
                    if ($process && $process->isRunning()) {
                        $process->kill();
                    }
                    self::kill();
                });
            }

            self::onFileChange(
                entry: $entry,
                libraries: $libraries,
                resources: $resources,
                callback: function() use (&$change) {
                    $change = true;
                },
            );
            
            async(function() use ($process, $stdin) {
                $writer = $process->getStdin();
                while ($chunk = $stdin->read()) {
                    if (!$process) {
                        continue;
                        $writer = false;
                    }
                    if (!$writer) {
                        $writer = $process->getStdin();
                    }
                    $writer->write($chunk);
                }
            });

            while (true) {
                $out = async(function() use ($process, $stdout) {
                    $reader = $process->getStdout();
                    while ($chunk = $reader->read()) {
                        $stdout->write($chunk);
                    }
                });
        
                $err = async(function() use ($process, $stderr) {
                    $reader = $process->getStderr();
                    while ($chunk = $reader->read()) {
                        $stderr->write($chunk);
                    }
                });
        
                $process->join();
                
                while (!$change) {
                    delay(1);
                }
                $change = false;

                awaitAll([$out,$err]);

                echo "Spawning $instruction".PHP_EOL;
                $process = Process::start($instruction);
            }
        })->await();
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
            $fs        = new Filesystem(createDefaultDriver());
            $changes   = [];
            $firstPass = true;

            while (true) {
                clearstatcache();
                $countLastPass = count($changes);

                $filenames = [$entry => false];
                foreach ([...$libraries, ...$resources] as $directory) {
                    if (!exists($directory)) {
                        continue;
                    }
                    foreach (listFilesRecursively(\realpath($directory)) as $i => $filename) {
                        $filenames[$filename] = false;
                    }
                }


                $countThisPass = count($filenames);
                if (!$firstPass && $countLastPass !== $countThisPass) {
                    $callback();
                }

                foreach (array_keys($filenames) as $filename) {
                    if (!exists($filename)) {
                        unset($changes[$filename]);
                        continue;
                    }
                    $mtime = $fs->getModificationTime($filename);
                    if (!isset($changes[$filename])) {
                        $changes[$filename] = $mtime;
                        continue;
                    }

                    if ($changes[$filename] !== $mtime) {
                        $changes[$filename] = $mtime;
                        $callback();
                    }
                }

                $firstPass = false;
                delay(1);
            }
        });
    }
}
