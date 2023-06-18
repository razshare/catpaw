<?php

namespace CatPaw\Utilities;

use function Amp\async;
use Attribute;
use BadFunctionCallException;
use CatPaw\Attributes\AttributeResolver;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Service;
use CatPaw\Attributes\Singleton;

use CatPaw\Bootstrap;
use function CatPaw\isPhar;
use Closure;
use Exception;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use RegexIterator;
use SplFixedArray;
use Throwable;

class Container {
    private function __construct() {
    }

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
        $table = AsciiTable::create();
        $table->add("Singleton");
        foreach (self::$singletons as $classname) {
            $table->add(\get_class($classname));
        }
        return $table->__toString().PHP_EOL;
    }

    /**
     * Check if an singleton of a class exists in the container.
     * @param  string $className
     * @return bool
     */
    public static function isset(string $className): bool {
        return isset(self::$singletons[$className]);
    }

    /**
     * Set a singleton inside the container.
     * @param  string $name
     * @param  mixed  $object
     * @return void
     */
    public static function set(string $name, mixed $object): void {
        self::$singletons[$name] = $object;
    }

    /**
     * Set a singleton inside the container.
     * @param  string $className
     * @param  mixed  $object
     * @return void
     * @deprecated use Container::set instead
     */
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

    /**
     * Delete all singletons and cache inside the container.
     * @return void
     */
    public static function clearAll():void {
        self::$cache      = [];
        self::$singletons = [];
    }

    /**
     * Run the entry method of an instance of a class.
     * @param  object                  $instance
     * @param  array<ReflectionMethod> $methods  methods of the instance
     * @throws ReflectionException
     * @return void
     */
    public static function entry(object $instance, array $methods):void {
        /** @var ReflectionMethod $method */
        foreach ($methods as $method) {
            $entry = Entry::findByMethod($method);
            if ($entry) {
                $args = Container::dependencies($method);
                if ($method->isStatic()) {
                    // async(function() use ($method, &$args):mixed {
                    //     return $method->invoke(null, ...$args);
                    // })->await();
                    $method->invoke(null, ...$args);
                } else {
                    // async(function() use ($method, $instance, &$args):mixed {
                    //     return $method->invoke($instance, ...$args);
                    // })->await();
                    $method->invoke($instance, ...$args);
                }
                break;
            }
        }
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
                $type  = $refparams[$i]->getType();
                $type  = ReflectionTypeManager::unwrap($refparams[$i]);
                $cname = $type ? $type->getName() : '';

                if ('bool' === $cname) {
                    $negative = false;
                } else {
                    $negative = null;
                }


                $default = ($defaultArguments[$i] ?? $negative)?$defaultArguments[$i]:$negative;
                if (!$default && $refparams[$i]->isOptional()) {
                    $default = $refparams[$i]->getDefaultValue() ?? $negative;
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
     * Find (& create) all the `dependencies` of a function or method.
     * The word `dependencies` includes `singletons` and certain `attribute` injections.
     * @param  ReflectionFunction|ReflectionMethod $reflection       the function/method to scan.
     * @param  false|array                         $options
     * @param  mixed                               $defaultArguments
     * @return array
     */
    public static function dependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        false|array $options = false,
        ...$defaultArguments
    ):array {
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
                "string"  !== $cname
                & "int"   !== $cname
                & "float" !== $cname
                & "bool"  !== $cname
                & "array" !== $cname
                & ""      !== $cname
            ) {
                $parameters[$i] = Container::create($cname);
            }

            $alen = $cache[self::PARAMETERS_ATTRIBUTES_LEN][$i];

            for ($j = 0; $j < $alen; $j++) {
                /** @var Closure $closure */
                $findByParameter   = $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES][$i][$j];
                $attributeInstance = $findByParameter($refparams[$i]);
                if (!$attributeInstance) {
                    continue;
                }
                // async(function() use (
                //     $attributeInstance,
                //     $refparams,
                //     $i,
                //     &$parameters,
                //     $context,
                // ):mixed {
                //     return $attributeInstance->onParameterMount(
                //         $refparams[$i],
                //         $parameters[$i],
                //         $context,
                //     );
                // })->await();
                $attributeInstance->onParameterMount(
                    $refparams[$i],
                    $parameters[$i],
                    $context,
                );
                $attributeHasStorage = $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i][$j];
                if ($attributeHasStorage) {
                    $parameters[$i] = &$parameters[$i]->storage();
                }
            }
        }

        return $parameters;
    }

    /**
     * Loads singletons from some locations (only directories are allowed for now).
     * @param  array<string> $locations directories containing your singletons.
     * @param  bool          $append    if true, the found singletons will be appended, otherwise all the other singletons will 
     *                                  be cleared before scanning.
     * @throws Exception
     * @return array<string> list of directories examined.
     */
    public static function load(
        array $locations,
        bool $append = false,
    ):array {
        if (!$append) {
            Container::clearAll();
        }

        if (!isset(self::$singletons[LoggerInterface::class])) {
            Container::set(LoggerInterface::class, LoggerFactory::create());
        }
        
        $scanned = [];
        $isPhar  = isPhar();
        foreach ($locations as $location) {
            if ('' === \trim($location)) {
                continue;
            }

            if ($isPhar) {
                $location = \Phar::running()."/$location";
            }

            try {
                $directory = new RecursiveDirectoryIterator($location);
            } catch (Throwable) {
                echo("Path \"$location\" is not a valid directory to load.".\PHP_EOL);
                /** @psalm-suppress UndefinedClass */
                Bootstrap::kill();
            }
                
            /**
             * @psalm-suppress PossiblyUndefinedVariable
             */
            $iterator = new RecursiveIteratorIterator($directory);
            $regex    = new RegexIterator(
                $iterator,
                '/^.+\.php$/i',
                RecursiveRegexIterator::GET_MATCH
            );

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
            if (!Singleton::findByClass(new ReflectionClass($classname))) {
                continue;
            }
            Container::create($classname);
        }
            
        return $scanned;
    }

    /**
     * Execute all attributes attatched to the function.
     * 
     * Will not execute the function itself and parameter attributes will not be instantiated at all.
     * @param  Closure|ReflectionFunction $function
     * @return void
     */
    public static function touch(Closure|ReflectionFunction $function) {
        if ($function instanceof Closure) {
            $reflection = new ReflectionFunction($function);
        } else {
            $reflection = $function;
            $function   = $reflection->getClosure();
        }

        foreach ($reflection->getAttributes() as $attribute) {
            $attributeArguments = $attribute->getArguments();
            /** @var class-string */
            $className = $attribute->getName();
            $klass     = new ReflectionClass($className);
            /**
             * @psalm-suppress ArgumentTypeCoercion
             */
            $entry  = self::findEntryMethod($klass);
            $object = $klass->newInstance(...$attributeArguments);
            if ($entry) {
                $arguments = Container::dependencies($entry);
                $entry->invoke($object, ...$arguments);
            }
        }
    }

    /**
     * Inject dependencies into a function and invoke it.
     * @param  Closure|ReflectionFunction $function
     * @param  bool                       $touch    if true, Container::touch will be 
     *                                              called automatically on the function
     * @throws BadFunctionCallException
     * @return void
     */
    public static function run(
        Closure|ReflectionFunction $function,
        bool $touch = true,
    ):void {
        if ($function instanceof Closure) {
            $reflection = new ReflectionFunction($function);
        } else {
            $reflection = $function;
            $function   = $reflection->getClosure();
        }

        if ($touch) {
            self::touch($function);
        }
            
        // if (!$function) {
            //     throw new BadFunctionCallException("Could not execute function \"{$reflection->getName()}\" inside container.");
        // }

        $arguments = Container::dependencies($reflection);
        $function(...$arguments);
    }

    /**
     * 
     * @param  ReflectionClass        $klass
     * @throws ReflectionException
     * @return ReflectionMethod|false
     */
    private static function findEntryMethod(ReflectionClass $klass): ReflectionMethod|false {
        foreach ($klass->getMethods() as $method) {
            if (($attributes = $method->getAttributes(Entry::class))) {
                /**
                 * @psalm-suppress RedundantConditionGivenDocblockType
                 */
                if (count($attributes) > 0) {
                    return $method;
                }
            }
        }
        return false;
    }

    /**
     * Make a new instance of the given class.<br />
     * This method will take care of dependency injections.
     * @param  string       $className           full name of the class.
     * @param  mixed        ...$defaultArguments
     * @return false|object
     */
    public static function create(
        string $className,
        ...$defaultArguments
    ) {
        if ('callable' === $className) {
            return false;
        }

        if (self::$singletons[$className] ?? false) {
            return self::$singletons[$className];
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $reflection = new ReflectionClass($className);

        if (
            $reflection->isInterface()
            || AttributeResolver::issetClassAttribute($reflection, Attribute::class)
            || count($reflection->getAttributes()) === 0
        ) {
            return false;
        }

        $service   = Service::findByClass($reflection);
        $singleton = Singleton::findByClass($reflection);

        $constructor = $reflection->getConstructor() ?? false;
        $arguments   = [];
        if ($constructor) {
            $arguments = self::dependencies($constructor, false, ...$defaultArguments);
        }

        $instance = new $className(...$arguments);

        if ($singleton || $service) {
            self::$singletons[$className] = $instance;
            if ($service) {
                $service->onClassMount($reflection, $instance, false);
            }
        }
            
        self::entry($instance, $reflection->getMethods());
            
        return $instance;
    }
}
