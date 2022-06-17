<?php

namespace CatPaw;

use function Amp\call;
use function Amp\delay;

use Amp\File\File;
use Amp\Loop;
use Amp\Promise;
use CatPaw\Attributes\Entry;
use CatPaw\Utilities\Container;
use CatPaw\Utilities\Dir;
use CatPaw\Utilities\LoggerFactory;
use CatPaw\Utilities\Strings;
use Generator;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;

use ReflectionFunction;

use Throwable;

class Bootstrap {
    private static function checkEntryChange(string $fileName, array &$store) {
        $size       = filesize($fileName);
        $lastChange = filemtime($fileName);
        if (isset($store[$fileName]) && $store[$fileName]['lastChange'] < $lastChange) {
            Loop::stop();
        } else {
            $store[$fileName] = [
                'name'       => $fileName,
                'size'       => $size,
                'lastChange' => $lastChange,
            ];
        }
    }

    private static function checkFileChange(string $dirname, array &$store) {
        $files = [];
        Dir::findFilesRecursive($dirname, $files);

        foreach ($files as $file) {
            $name = $file['name'];
            if (isset($store[$name])) {
                if ($store[$name]['lastChange'] < $file['lastChange']) {
                    Loop::stop();
                    return;
                }
            }
            $store[$name] = $file;
        }
    }


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
     * @param  string    $file    php file to run
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

        Container::setObject(LoggerInterface::class, LoggerFactory::create($name));

        if (!$file) {
            die(Strings::red("Please point to a php entry file.\n"));
        }

        Loop::run(function() use ($file, $onkill, $singletons, $verbose, $watch) {
            /** @var array<string> $filenames */
            $filenames   = yield Container::load(\preg_split('/,|;/', $singletons));
            $filenames[] = $file;
            if ($watch) {
                self::watch($filenames, $verbose);
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
    
    private static function watch(
        array $filenames,
        bool $verbose = false,
    ):Promise {
        $changes = [];
        return call(function() use ($filenames, &$changes, $verbose) {
            while (true) {
                /** @var LoggerInterface $logger */
                $logger = yield Container::create(LoggerInterface::class);
                foreach ($filenames as $filename) {
                    $changed = filemtime($filename);
                    if (str_ends_with($filename, "main.php")) {
                        $logger->info("changed: $changed");
                    }
                    if (!isset($changes[$filename])) {
                        $changes[$filename] = $changed;
                        if ($verbose) {
                            $logger->info("Watching $filename");
                        }
                        continue;
                    }
                    if ($changes[$filename] !== $changed) {
                        foreach (self::$onKillActions as $callback) {
                            yield call($callback);
                        }
                            
                        $logger->info("Killing application...");
                        die();
                    }
                }
                yield delay(1000);
            }
        });
    }
}
