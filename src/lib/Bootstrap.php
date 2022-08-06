<?php

namespace CatPaw;

use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;

use function Amp\call;
use function Amp\delay;
use function Amp\File\createDefaultDriver;

use Amp\File\File;
use Amp\File\Filesystem;

use Amp\Loop;
use Amp\Process\Process;
use Amp\Promise;
use CatPaw\Attributes\Entry;
use CatPaw\Utilities\Container;
use CatPaw\Utilities\LoggerFactory;
use CatPaw\Utilities\Strings;
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
        if (is_file($filename)) {
            $filename = realpath($filename);
            $owd      = getcwd();
            chdir(dirname($filename));
            require_once $filename;
            chdir($owd);
            /** @var mixed $result */
            if (!function_exists('main')) {
                die(Strings::red("Please define a global main function.\n"));
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
            die(Strings::red("Could not find php entry file \"$filename\".\n"));
        }
    }

    /**
     * Bootstrap an application from a file.
     * @param  string    $name    application name (this will be used by the default logger)
     * @param  string    $file    php file to run (absolute path)
     * @param  string    $verbose if true, will log all singletons loaded at startup
     * @param  string    $watch
     * @param  mixed     $onkill
     * @throws Throwable
     */
    public static function start(
        string $file,
        string $name,
        string $singletons,
        bool $verbose = false,
        bool $watch = false,
    ) {
        set_time_limit(0);
        ob_implicit_flush();
        ini_set('memory_limit', '-1');
        $_ENV['CATPAW_WATCHING'] = $watch;

        Container::setObject(LoggerInterface::class, LoggerFactory::create($name));

        if (!$file) {
            die(Strings::red("Please point to a php entry file.\n"));
        }

        Loop::run(function() use ($file, $singletons, $verbose, $watch) {
            /** @var array<string> $filenames */
            $directories = \preg_split('/,|;/', $singletons);
            yield Container::load($directories);
            if ($watch) {
                self::watch($file, $directories, $verbose);
            }

            if ($verbose) {
                echo Container::describe();
            }
            yield from self::init($file);
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
    
    public static function spawn(
        string $start,
        string $file,
        string $name,
        string $singletons,
        bool $verbose = false,
        bool $watch = false,
    ) {
        Loop::run(function() use (
            $start,
            $file,
            $name,
            $singletons,
            $verbose,
            $watch,
        ) {
            $out = new ResourceOutputStream(STDOUT);
            $err = new ResourceOutputStream(STDERR);
            $in  = new ResourceInputStream(STDIN);

            $options = [ "-f\"$file\"" ];

            if ($singletons) {
                $options[] = "-s\"$singletons\"";
            }
            if ($verbose) {
                $options[] = '-v';
            }
            if ($watch) {
                $options[] = '-w';
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
                        $out->write($chunk);
                    }
                });

                call(function() use ($perr, $err) {
                    while ($chunk = yield $perr->read()) {
                        $err->write($chunk);
                    }
                });

                call(function() use ($pin, $in) {
                    while ($chunk = yield $in->read()) {
                        $pin->write($chunk);
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

    private static function watch(
        string $entry,
        array $directories,
        bool $verbose = false,
    ):Promise {
        return call(function() use ($entry, $directories, $verbose) {
            $fs        = new Filesystem(createDefaultDriver());
            $changes   = [];
            $firstPass = true;
            /** @var LoggerInterface $logger */
            $logger = yield Container::create(LoggerInterface::class);

            while (true) {
                clearstatcache();
                $countLastPass = count($changes) + 1;   // +1 because of the entry file

                $filenames = [ $entry ];
                foreach ($directories as $directory) {
                    $filenames = [...$filenames, ...(yield listFilesRecursive($directory))];
                }

                $countThisPass = count($filenames);
                if (!$firstPass && $countLastPass !== $countThisPass) {
                    $logger->info("Killing application...");
                    yield self::kill();
                }

                foreach ($filenames as $filename) {
                    if (!str_ends_with($filename, '.php')) {
                        continue;
                    }
                    
                    
                    $mtime = yield $fs->getModificationTime($filename);
                    if (!isset($changes[$filename])) {
                        $changes[$filename] = $mtime;
                        if ($verbose) {
                            $logger->info("Watching $filename");
                        }
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
