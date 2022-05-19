<?php

namespace CatPaw\Utilities;

use Amp\LazyPromise;
use Amp\Promise;
use Closure;
use Exception;

class ClassFinder {
    //This should be the directory that contains composer.json
    private string $appRoot;

    public function setAppRoot($root) {
        $this->appRoot = $root;
    }

    public function getClassesInNamespace(string $namespace, Closure $onSubDir = null): Promise {
        return new LazyPromise(function() use ($namespace, $onSubDir) {
            $namespace = preg_replace('/\\\\+(?=$)/', '', str_replace("\\\\", "\\", $namespace));
            $dirs = $this->getNamespaceDirectories($namespace);
            $results = [];
            $subdirs = [];    //an array of promisses that resolve subdirectories
            if ($dirs) {
                foreach ($dirs as $dir) {
                    $files = scandir($dir);
                    $classes = array_map(function($file) use (&$namespace, &$onSubDir, &$dir, &$subdirs) {
                        if (null !== $onSubDir && '.' !== $file && '..' !== $file && is_dir($dir.'/'.$file)) {
                            $subdirs[] = $onSubDir($file);
                        }
                        return $namespace."\\".str_replace('.php', '', $file);
                    }, $files);

                    $results[] = array_filter($classes, function(string $possibleClass) {
                        return class_exists($possibleClass);
                    });
                }
            }
            yield $subdirs;
            return array_merge(...$results);
        });
    }

    /**
     * @throws Exception
     */
    public function getModulesInNamespace(string $namespace, Closure $onSubDir = null): array {
        $namespace = preg_replace('/\\\\+(?=$)/', '', str_replace("\\\\", "\\", $namespace));
        $dirs = $this->getNamespaceDirectories($namespace);
        if (!$dirs) {
            $dirs = [];
        }
        $results = [];
        foreach ($dirs as $dir) {
            $files = scandir($dir);
            $modules = array_map(function($file) use (&$namespace, &$onSubDir, &$dir) {
                if (null !== $onSubDir && '.' !== $file && '..' !== $file && is_dir($dir.'/'.$file)) {
                    $onSubDir($file);
                }
                return join('/', [
                    $dir,
                    $file,
                ]);
            }, $files);

            $results[] = array_filter($modules, function(string $possibleModule) {
                return str_ends_with($possibleModule, '.module.php');
            });
        }

        return array_merge(...$results);
    }

    public function getDefinedNamespaces(): array {
        $composerJsonPath = $this->appRoot.'composer.json';
        $composerConfig = json_decode(file_get_contents($composerJsonPath), true);

        $autoload = !isset($composerConfig['autoload']['psr-4']) ? [] : $composerConfig['autoload']['psr-4'];
        $autoloadDev = !isset($composerConfig['autoload-dev']['psr-4']) ? [] : $composerConfig['autoload-dev']['psr-4'];
        return array_merge_recursive($autoload, $autoloadDev);
    }

    /**
     * @throws Exception
     */
    public function getNamespaceDirectories(string $namespace): array|false {
        $result = [];
        $composerNamespaces = $this->getDefinedNamespaces();
        if (!is_iterable($composerNamespaces)) {
            $composerNamespaces = [$composerNamespaces];
        }

        $namespaceFragments = explode("\\", $namespace);
        $undefinedNamespaceFragments = [];

        while ($namespaceFragments) {
            $possibleNamespace = implode("\\", $namespaceFragments);
            if (!str_ends_with($possibleNamespace, "\\")) {
                $possibleNamespace .= "\\";
            }

            if ("\\" === $possibleNamespace) {
                $possibleNamespace = '';
            }
            if (array_key_exists($possibleNamespace, $composerNamespaces)) {
                if (!is_iterable($composerNamespaces[$possibleNamespace])) {
                    $composerNamespaces[$possibleNamespace] = [$composerNamespaces[$possibleNamespace]];
                }

                foreach ($composerNamespaces[$possibleNamespace] as $dir) {
                    $real = realpath($this->appRoot.$dir.implode('/', $undefinedNamespaceFragments));
                    if (!$real) {
                        throw new Exception("Directory $dir from namespace $possibleNamespace doesn't seem to exist");
                    }
                    $result[] = $real;
                }
            }

            array_unshift($undefinedNamespaceFragments, array_pop($namespaceFragments));
        }

        return count($result) > 0 ? $result : false;
    }
}