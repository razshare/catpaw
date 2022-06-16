<?php

namespace CatPaw;

use Amp\Loop;
use CatPaw\Attributes\Entry;
use CatPaw\Utilities\Container;
use CatPaw\Utilities\Dir;
use CatPaw\Utilities\LoggerFactory;
use CatPaw\Utilities\Strings;
use Closure;
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

    private static function watch(string $entryFileName, array $dirs, int $sleep) {
        $store = [];

        self::checkEntryChange($entryFileName, $store);

        foreach ($dirs as $dir) {
            self::checkFileChange($dir, $store);
        }


        Loop::repeat($sleep, function() use ($dirs, $store, $entryFileName) {
            self::checkEntryChange($entryFileName, $store);
            foreach ($dirs as $dir) {
                self::checkFileChange($dir, $store);
            }
        });
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
     * @param  string       $name     application name (this will be used by the default logger)
     * @param  string       $file     php file to run
     * @param  string       $verbose  if true, will log all singletons loaded at startup
     * @param  bool|Closure $callback
     * @throws Throwable
     */
    public static function start(
        string $file,
        string $name,
        ?string $singletons = null,
        bool $verbose = false,
        false|Closure $callback = false
    ) {
        set_time_limit(0);
        ob_implicit_flush();
        ini_set('memory_limit', '-1');

        Container::setObject(LoggerInterface::class, LoggerFactory::create($name));

        if (!$file) {
            die(Strings::red("Please point to a php entry file.\n"));
        }

        Loop::run(function() use ($file, $callback, $singletons, $verbose) {
            if ($singletons) {
                yield Container::load(\preg_split('/,|;/', $singletons));
            }
                
            if ($verbose) {
                echo Container::describe();
            }
            yield from self::init($file);
            if ($callback) {
                yield \Amp\call($callback);
            }
        });
    }
}
