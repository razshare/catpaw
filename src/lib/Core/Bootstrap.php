<?php

namespace CatPaw\Core;

use function Amp\async;
use function Amp\ByteStream\getStdin;
use function Amp\delay;

use CatPaw\Core\Implementations\Environment\SimpleEnvironment;
use CatPaw\Core\Interfaces\EnvironmentInterface;
use Error;
use function preg_split;
use Psr\Log\LoggerInterface;

use ReflectionFunction;
use Revolt\EventLoop;
use Throwable;

class Bootstrap {
    private function __construct() {
    }
    

    /**
     * Initialize an application from a source file (that usually defines a global "main" function).
     * @param  string        $fileName
     * @return Result<mixed>
     */
    public static function initialize(string $fileName):Result {
        if (File::exists($fileName)) {
            require_once($fileName);
        }


        if (!function_exists('main')) {
            return error("Could not find a global main function.");
        }

        $main = new ReflectionFunction('main');

        Container::touch($main);
        return Container::run($main, false);
    }

    /**
     * Bootstrap an application from a file.
     * @param  string        $main        the main file of the application (it usually defines a global "main" function)
     * @param  string        $name        application name (this will be used by the default logger)
     * @param  array<string> $libraries   libraries to load
     * @param  array<string> $resources   resources to load
     * @param  string        $environment environment to load
     * @param  string        $wait        if true, the application will hang when it terminates
     * @return void
     */
    public static function start(
        string $main,
        string $name,
        array $libraries,
        array $resources,
        string $environment,
        string $wait,
    ):void {
        try {
            foreach ($libraries as $library) {
                Container::requireLibraries($library)->unwrap($requireError);
                if ($requireError) {
                    self::kill((string)$requireError);
                }
            }
            
            Container::loadDefaultProviders($name)->unwrap($initializeError);
            if ($initializeError) {
                self::kill((string)$initializeError);
            }

            $main = (string)FileName::create($main);

            $logger = Container::get(LoggerInterface::class)->unwrap($loggerError);
            if ($loggerError) {
                self::kill((string)$loggerError);
            }

            $env = new SimpleEnvironment($logger);
            Container::provide(EnvironmentInterface::class, $env);
            $env->load()->unwrap($envLoadError);
            if ($envLoadError) {
                self::kill((string)$envLoadError);
            }

            $env->set('MAIN', $main);
            $env->set('LIBRARIES', $libraries);
            $env->set('RESOURCES', $resources);

            if ($environment) {
                $env->withFileName($environment);
                $env->load()->unwrap($error);
                if ($error) {
                    self::kill((string)$error);
                }
            }

            foreach ($libraries as &$library) {
                $libraryLocal = (string)FileName::create($library);
                if ('' === $libraryLocal) {
                    self::kill("Trying to load php library `$library`, but the directory doesn't seem to exist.");
                }
                $library = $libraryLocal;
            }

            foreach ($resources as $resource) {
                $resourceLocal = (string)FileName::create($resource);
                if ('' === $resourceLocal) {
                    self::kill("Trying to track resource `$resource`, but it doesn't seem to exist.");
                }
                $resource = $resourceLocal;
            }

            self::initialize($main)->unwrap($initializeError);

            if ($initializeError) {
                self::kill((string)$initializeError);
            }

            if ($wait) {
                // @phpstan-ignore while.alwaysTrue
                while (true) {
                    delay(0.1);
                }
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
    public static function onKill(callable $function):void {
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
     * @param  string        $spawner
     * @param  string        $fileName
     * @param  array<string> $arguments
     * @return void
     */
    public static function spawn(
        string $spawner,
        string $fileName,
        array $arguments,
    ):void {
        try {
            EventLoop::onSignal(SIGHUP, static fn () => self::kill("Killing application..."));
            EventLoop::onSignal(SIGINT, static fn () => self::kill("Killing application..."));
            EventLoop::onSignal(SIGQUIT, static fn () => self::kill("Killing application..."));
            EventLoop::onSignal(SIGTERM, static fn () => self::kill("Killing application..."));

            async(static function() use (
                $spawner,
                $fileName,
                $arguments,
            ) {
                if (!Container::isProvided(LoggerInterface::class)) {
                    $logger = LoggerFactory::create()->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    Container::provide(LoggerInterface::class, $logger);
                } else {
                    $logger = Container::get(LoggerInterface::class)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                }

                foreach ($arguments as &$argument) {
                    $parts = preg_split('/=|\s/', $argument, 2);
                    if (count($parts) < 2) {
                        continue;
                    }

                    $left     = $parts[0];
                    $right    = $parts[1];
                    $slashed  = addslashes($right);
                    $argument = "$left=\"$slashed\"";
                }

                $argumentsStringified = join(' ', $arguments);
                $instruction          = "$spawner $fileName $argumentsStringified";

                echo "Spawning $instruction".PHP_EOL;

                if (DIRECTORY_SEPARATOR === '/') {
                    EventLoop::onSignal(SIGINT, static function() {
                        self::kill();
                    });
                }

                $kill  = new Signal;
                $stdin = getStdin();

                async(function() use ($stdin, $kill) {
                    // @phpstan-ignore while.alwaysTrue
                    while (true) {
                        $content = $stdin->read();
                        if (!$content) {
                            delay(0.1);
                            continue;
                        }
                        
                        $kill->send();
                        $kill->clear();
                    }
                });

                // @phpstan-ignore while.alwaysTrue
                while (true) {
                    $code = Process::execute($instruction, out(), kill: $kill)->unwrap($error);

                    
                    if ($error) {
                        echo $error.PHP_EOL;
                        continue;
                    }
                    
                    if (0 !== $code && 137 !== $code) {
                        echo "main function terminated with exit code $code".PHP_EOL;
                        continue;
                    }
                }
            });

            EventLoop::run();
        } catch (Throwable $error) {
            self::kill($error);
        }
    }
}
