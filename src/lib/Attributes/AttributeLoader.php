<?php

namespace CatPaw\Attributes;

use function Amp\call;
use Amp\LazyPromise;
use Amp\Promise;
use CatPaw\Utilities\BooleanAction;
use CatPaw\Utilities\ClassFinder;
use CatPaw\Utilities\Container;

use Exception;

#[Singleton]
class AttributeLoader {
    private ClassFinder $finder;
    private string      $location = '';

    public function __construct() {
        $this->finder = new ClassFinder();
    }

    /**
     * Set the location of the loader.<br/>
     * Usually it should be set to __DIR__.
     * @param  string          $location location to set.
     * @return AttributeLoader this AttributeLoader for a fluent api.
     */
    public function setLocation(string $location): AttributeLoader {
        $this->location = $location;
        if (!str_ends_with($this->location, '/')) {
            $this->location .= '/';
        }
        $this->finder->setAppRoot($this->location);
        return $this;
    }

    /**
     * Same as "loadModulesFromNamespace", but allows you to decide which (found) classes to skip or let through for loading.
     * @param  string        $namespace   location to set.
     * @param  BooleanAction $checkModule a  BooleanAction that dictates wether or not a class should be loaded or not by returning "true" or "false".
     * @throws Exception
     * @return $this         this AttributeLoader for a fluent api.
     */
    public function loadSomeModulesFromNamespace(string $namespace, BooleanAction $checkModule): AttributeLoader {
        $this->finder->setAppRoot($this->location);
        $modules = $this->finder->getModulesInNamespace($namespace);
        $owd = getcwd();
        foreach ($modules as $module) {
            if ($checkModule->run($module)) {
                $f = function($module) use ($owd) {
                    chdir(dirname($module));
                    require_once $module;
                    chdir($owd);
                };
                $f($module);
            }
        }

        return $this;
    }

    /**
     * Same as "loadClassesFromNamespace", but allows you to decide which (found) classes to skip or let through for loading.
     * @param  string                   $namespace      location to set.
     * @param  BooleanAction            $checkClassname a BooleanAction that dictates whether a class should be loaded or not by returning "true" or "false", or not.
     * @return Promise<AttributeLoader> this AttributeLoader
     */
    public function loadSomeClassesFromNamespace(string $namespace, BooleanAction $checkClassname): Promise {
        return call(function() use ($namespace, $checkClassname) {
            $this->finder->setAppRoot($this->location);
            $classnames = $this->finder->getClassesInNamespace($namespace);
            foreach ($classnames as $className) {
                if ($checkClassname->run($className)) {
                    yield Container::create($className);
                }
            }

            return $this;
        });
    }

    /**
     * Load classes inside a specific namespace.
     * @param  string                   $namespace root namespace of the list of classes you want to load.
     * @return Promise<AttributeLoader> this AttributeLoader
     */
    public function loadClassesFromNamespace(string $namespace = ''): Promise {
        return call(function() use ($namespace) {
            $this->finder->setAppRoot($this->location);
            $classnames = yield $this->finder->getClassesInNamespace(
                $namespace,
                fn(string $dirname) => call(fn() => yield $this->loadClassesFromNamespace("$namespace\\$dirname"))
            );
            foreach ($classnames as $className) {
                yield Container::create($className);
            }


            return $this;
        });
    }

    /**
     * Load Modules inside a specific namespace.<br/>
     * Note that module file names **must** end with the extension ".module.php".
     * @param  string          $namespace root namespace of the list of Modules you want to load.
     * @throws Exception
     * @return AttributeLoader this AttributeLoader for a fluent api.
     */
    public function loadModulesFromNamespace(string $namespace = ''): AttributeLoader {
        $this->finder->setAppRoot($this->location);
        $modules = $this->finder->getModulesInNamespace($namespace, function($dirname) use ($namespace) {
            return $this->loadModulesFromNamespace("$namespace\\$dirname");
        });
        $owd = getcwd();
        foreach ($modules as $module) {
            $f = function($module) use ($owd) {
                chdir(dirname($module));
                require_once $module;
                chdir($owd);
            };
            $f($module);
        }
        return $this;
    }

    /**
     * List of examined directories.
     * @param  string        $namespace
     * @throws Exception
     * @return array<string>
     */
    public function getNamespaceDirectories(string $namespace): array {
        $namespace = preg_replace('/\\\\+(?=$)/', '', str_replace("\\\\", "\\", $namespace));
        return $this->finder->getNamespaceDirectories($namespace) ?? [];
    }

    public function getDefinedNamespaces(): array {
        return $this->finder->getDefinedNamespaces();
    }
}