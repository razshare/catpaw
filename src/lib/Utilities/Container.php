<?php

namespace CatPaw\Utilities;

use function Amp\call;
use Amp\Promise;
use Attribute;
use CatPaw\Attributes\AttributeResolver;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Service;
use CatPaw\Attributes\Singleton;
use Closure;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionUnionType;
use RegexIterator;
use SplFixedArray;

class Container {
    private static array $cache      = [];
    private static array $singletons = [];

    public static function getSingletons():array {
        return self::$singletons;
    }

    /**
     * Returns an ASCI table describing all existing routes.
     * @return string
     */
    public static function describe(): string {
        $table = new AsciiTable();
        $table->add("Singleton");
        foreach (self::$singletons as $classname) {
            $table->add(\get_class($classname));
        }
        return $table->toString().PHP_EOL;
    }

    public static function isset(string $className): bool {
        return isset(self::$singletons[$className]);
    }

    public static function setObject(string $className, mixed $object): void {
        self::$singletons[$className] = $object;
    }

    private const PARAMETERS_INIT_VALUE              = 0;
    private const REFLECTION_PARAMETERS              = 1;
    private const PARAMETERS_LEN                     = 2;
    private const PARAMETERS_CNAMES                  = 3;
    private const PARAMETERS_ATTRIBUTES_LEN          = 4;
    private const PARAMETERS_ATTRIBUTES_CLOSURES     = 5;
    private const PARAMETERS_ATTRIBUTES_HAVE_STORAGE = 6;

    public static function clearAll():void {
        self::$cache      = [];
        self::$singletons = [];
    }

    /**
     * Run the entry method of an instance.
     * @param  object                  $instance
     * @param  array<ReflectionMethod> $methods  methods of the instance
     * @throws ReflectionException
     * @return Promise
     */
    public static function entry(object $instance, array $methods):Promise {
        return call(function() use ($methods, $instance) {
            /** @var ReflectionMethod $method */
            foreach ($methods as $method) {
                $entry = yield Entry::findByMethod($method);
                if ($entry) {
                    $args = yield Container::dependencies($method);
                    if ($method->isStatic()) {
                        yield \Amp\call(fn() => $method->invoke(null, ...$args));
                    } else {
                        yield \Amp\call(fn() => $method->invoke($instance, ...$args));
                    }
                    break;
                }
            }
        });
    }


    /**
     * @throws ReflectionException
     */
    private static function cacheInMethodOrFunctionDependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        string $key1,
        string $key2,
        ...$defaultArguments
    ): void {
        if (!isset(self::$cache[$key1])) {
            self::$cache[$key1] = [];
        }

        if (!isset(self::$cache[$key1][$key2])) {
            self::$cache[$key1][$key2] = [];

            $cache = new SplFixedArray(8);

            $refparams  = $reflection->getParameters();
            $len        = count($refparams);
            $parameters = array_fill(0, $len, false);

            $cache[self::REFLECTION_PARAMETERS]              = $refparams;
            $cache[self::PARAMETERS_LEN]                     = $len;
            $cache[self::PARAMETERS_INIT_VALUE]              = new SplFixedArray($len);
            $cache[self::PARAMETERS_CNAMES]                  = new SplFixedArray($len);
            $cache[self::PARAMETERS_ATTRIBUTES_LEN]          = new SplFixedArray($len);
            $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES]     = new SplFixedArray($len);
            $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE] = new SplFixedArray($len);

            for ($i = 0; $i < $len; $i++) {
                $type = $refparams[$i]->getType();
                if ($type instanceof ReflectionUnionType) {
                    $cname = $type->getTypes()[0]->getName();
                } else {
                    $cname = $type ? $type->getName() : '';
                }

                $default = ($defaultArguments[$i] ?? false)?$defaultArguments[$i]:false;
                if (!$default && $refparams[$i]->isOptional()) {
                    $default = $refparams[$i]->getDefaultValue() ?? false;
                }


                $cache[self::PARAMETERS_CNAMES][$i]                  = $cname;
                $parameters[$i]                                      = $default;
                $cache[self::PARAMETERS_INIT_VALUE][$i]              = $parameters[$i];
                $attributes                                          = $refparams[$i]->getAttributes();
                $alen                                                = count($attributes);
                $cache[self::PARAMETERS_ATTRIBUTES_LEN][$i]          = $alen;
                $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i] = new SplFixedArray($alen);
                for ($j = 0; $j < $alen; $j++) {
                    $attribute = $attributes[$j];
                    $aname     = $attribute->getName();
                    $class     = new ReflectionClass($aname);
                    $method    = $class->getMethod("findByParameter");

                    $closures                                        = $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES][$i];
                    $closures[$j]                                    = $method->getClosure();
                    $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES][$i] = $closures;

                    $haveStorage                                         = $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i];
                    $haveStorage[$j]                                     = $class->hasMethod("storage");
                    $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i] = $haveStorage;
                }
            }
            self::$cache[$key1][$key2] = $cache;
        }
    }

    /**
     * 
     * @param  ReflectionFunction|ReflectionMethod $reflection
     * @param  bool|array                          $options
     * @return Promise<array<mixed>>
     */
    public static function dependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        false|array $options = false,
        ...$defaultArguments
    ): Promise {
        return call(function() use ($reflection, $options, $defaultArguments) {
            $context = false;
            if ($options) {
                [
                    "id"      => [$key1, $key2],
                    "force"   => $force,
                    "context" => $context,
                ] = $options;

                if (!isset(self::$cache[$key1][$key2])) {
                    self::cacheInMethodOrFunctionDependencies($reflection, $key1, $key2, ...$defaultArguments);
                }
                $cache = &self::$cache[$key1][$key2];
            } else {
                $fileName     = $reflection->getFileName();
                $functionName = $reflection->getName();
                if ($reflection instanceof ReflectionMethod) {
                    $class        = $reflection->getDeclaringClass();
                    $functionName = $class->getName().':'.$functionName;
                }
                if (!isset(self::$cache[$fileName][$functionName])) {
                    self::cacheInMethodOrFunctionDependencies($reflection, $fileName, $functionName, ...$defaultArguments);
                }
                $cache = &self::$cache[$fileName][$functionName];
            }

            $refparams  = $cache[self::REFLECTION_PARAMETERS];
            $len        = $cache[self::PARAMETERS_LEN];
            $parameters = array_fill(0, $len, false);

            for ($i = 0; $i < $len; $i++) {
                $parameters[$i] = $cache[self::PARAMETERS_INIT_VALUE][$i];
                $cname          = $cache[self::PARAMETERS_CNAMES][$i];

                if ($options && isset($force[$cname])) {
                    $parameters[$i] = $force[$cname];
                    continue;
                }

                if (
                    "string" !== $cname
                    & "int" !== $cname
                    & "float" !== $cname
                    & "bool" !== $cname
                    & "array" !== $cname
                    & "" !== $cname
                ) {
                    $parameters[$i] = yield Container::create($cname);
                }

                $alen = $cache[self::PARAMETERS_ATTRIBUTES_LEN][$i];

                for ($j = 0; $j < $alen; $j++) {
                    /** @var Closure $closure */
                    $findByParameter   = $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES][$i][$j];
                    $attributeInstance = yield $findByParameter($refparams[$i]);
                    if (!$attributeInstance) {
                        continue;
                    }
                    yield $attributeInstance->onParameter($refparams[$i], $parameters[$i], $context);
                    $attributeHasStorage = $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i][$j];
                    if ($attributeHasStorage) {
                        $parameters[$i] = &$parameters[$i]->storage();
                    }
                }
            }

            return $parameters;
        });
    }

    /**
     * Loads singletons from predefined locations.
     * @param  array<string>          $locations
     * @throws Exception
     * @return Promise<array<string>> list of directories examined.
     */
    public static function load(array $locations):Promise {
        return call(function() use ($locations) {
            $scanned = [];
            foreach ($locations as $location) {
                if ('' === \trim($location)) {
                    continue;
                }
                $directory = new RecursiveDirectoryIterator(realpath($location));
                $iterator  = new RecursiveIteratorIterator($directory);
                $regex     = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

                for ($regex->rewind(); $regex->valid() ; $regex->next()) {
                    /** @var array<string> $files */
                    $files = $regex->current();
                    foreach ($files as $filename) {
                        require_once($filename);
                        $scanned[] = $filename;
                    }
                }
            }


            foreach (get_declared_classes() as $classname) {
                if (!yield Singleton::findByClass(new ReflectionClass($classname))) {
                    continue;
                }
                yield Container::create($classname);
            }
            
            return $scanned;
        });
    }

    /**
     * Inject dependencies into a function and invoke it.
     * @param  Closure|ReflectionFunction $function
     * @param  array                      $defaultArguments
     * @return Promise<void>
     */
    public static function run(Closure|ReflectionFunction $function): Promise {
        return call(function() use ($function) {
            if ($function instanceof Closure) {
                $reflection = new ReflectionFunction($function);
            } else {
                $reflection = $function;
                $function   = $reflection->getClosure();
            }

            $arguments = yield Container::dependencies($reflection);
            yield call($function, ...$arguments);
        });
    }

    /**
     * Make a new instance of the given class.<br />
     * This method will take care of dependency injections.
     * @param  string          $className full name of the class.
     * @param  mixed           $args
     * @return Promise<object>
     */
    public static function create(
        string $className,
        ...$defaultArguments
    ): Promise {
        return call(function() use ($className, $defaultArguments) {
            if (self::$singletons[$className] ?? false) {
                return self::$singletons[$className];
            }

            $reflection = new ReflectionClass($className);

            if (
                $reflection->isInterface()
                || AttributeResolver::issetClassAttribute($reflection, Attribute::class)
                || count($reflection->getAttributes()) === 0
            ) {
                return false;
            }

            /** @var Service $service */
            $service = yield Service::findByClass($reflection);
            /** @var Singleton $singleton */
            $singleton = yield Singleton::findByClass($reflection);

            $constructor = $reflection->getConstructor() ?? false;
            $arguments   = [];
            if ($constructor) {
                $arguments = yield self::dependencies($constructor, false, ...$defaultArguments);
            }

            $instance = new $className(...$arguments);

            if (!$instance) {
                return false;
            }

            if ($singleton || $service) {
                self::$singletons[$className] = $instance;
                if ($service) {
                    yield $service->onClassInstantiation($reflection, $instance, false);
                }
            }
            
            yield self::entry($instance, $reflection->getMethods());
            
            return $instance;
        });
    }
}