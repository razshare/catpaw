<?php

namespace CatPaw;

use function Amp\async;
use function Amp\File\isDirectory;
use function Amp\File\isFile;

use Attribute;
use BadFunctionCallException;
use CatPaw\Attributes\AttributeResolver;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Service;
use CatPaw\Attributes\Singleton;

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

    private static function findFunctionDependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        DependenciesOptions $options,
    ) {
        $defaultArguments     = $options->defaultArguments;
        $reflectionParameters = $reflection->getParameters();
        $items                = [];
        foreach ($reflectionParameters as $key => $reflectionParameter) {
            $defaultArgument = $defaultArguments[$key] ?? null;
            $isOptional      = $reflectionParameter->isOptional();
            $defaultValue    = $reflectionParameter->isDefaultValueAvailable()?$reflectionParameter->getDefaultValue():null;
            $type            = ReflectionTypeManager::unwrap($reflectionParameter)?->getName() ?? '';
            $name            = $reflectionParameter->getName();
            $attributes      = $reflectionParameter->getAttributes();

            if ('bool' === $type) {
                $negative = false;
            } else {
                $negative = null;
            }

            $defaultCalculated = ($defaultArgument ?? $negative)?$defaultArgument:$negative;

            if (!$defaultCalculated && $isOptional) {
                $defaultValue = $defaultValue ?? $negative;
            } else {
                $defaultValue = $defaultCalculated;
            }

            $items[] = new DependencySearchResultItem(
                reflectionParameter: $reflectionParameter,
                defaultArgument: $defaultArgument,
                isOptional: $isOptional,
                defaultValue: $defaultValue,
                type: $type,
                name: $name,
                attributes: $attributes,
            );
        }

        return $items;
    }



    public static function dependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        false|DependenciesOptions $options = false,
    ):array {
        if (!$options) {
            $options = DependenciesOptions::create(
                ids: [],
                overwrites:[],
                provides: [],
                fallbacks: [],
                defaultArguments: [],
                context: false,
            );
        }
        $parameters = [];
        $results    = self::findFunctionDependencies($reflection, $options);
        foreach ($results as $key => $result) {
            $reflectionParameter = $result->reflectionParameter;
            $type                = $result->type;
            $name                = $result->name;
            $defaultValue        = $result->defaultValue;
            $provide             = $options->provides[$type]   ?? false;
            $overwrite           = $options->overwrites[$type] ?? false;
            $fallback            = $options->fallbacks[$type]  ?? false;
            $attributes          = $result->attributes;
            $numberOfAttributes  = count($result->attributes);

            if ($overwrite) {
                $parameters[$key] = $overwrite($result);
                continue;
            }

            if ($provide) {
                $parameters[$key] = $provide($result);
            }

            if (
                "string"  !== $type
                & "int"   !== $type
                & "float" !== $type
                & "bool"  !== $type
                & "array" !== $type
                & ""      !== $type
            ) {
                $parameters[$key] = Container::create($type);
            }

            if (0 === $numberOfAttributes) {
                if ($fallback) {
                    $parameters[$key] = $fallback($result);
                }
                continue;
            }
            
            foreach ($attributes as $attribute) {
                $attributeClass    = new ReflectionClass($attribute->getName());
                $findByParameter   = $attributeClass->getMethod('findByParameter')->getClosure();
                $hasStorage        = $attributeClass->hasMethod("storage");
                $attributeInstance = $findByParameter($reflectionParameter);
                if (!$attributeInstance) {
                    if ($fallback) {
                        $parameters[$key] = $fallback($result);
                    }
                    continue;
                }
                $attributeInstance->onParameterMount(
                    $reflectionParameter,
                    $parameters[$key],
                    $options,
                );
                if ($hasStorage) {
                    $parameters[$key] = &$parameters[$key]->storage();
                }
            }
        }

        return $parameters;
    }

    /**
     * Load libraries and create default singletons for the container.
     * @param  string        $path
     * @param  bool          $append if true, the resulting singletons will be appended to the container, otherwise all the other singletons will be cleared before scanning.
     * @throws Exception
     * @return array<string> list of files scanned.
     */
    public static function load(
        string $path,
        bool $append = false,
    ):array {
        if (!$append) {
            Container::clearAll();
        }

        if (!isset(self::$singletons[LoggerInterface::class])) {
            $logger = LoggerFactory::create();
            Container::set(LoggerInterface::class, $logger);
        } else {
            $logger = Container::create(LoggerInterface::class);
        }
        
        /** @var LoggerInterface $logger */

        $scanned = [];
        $isPhar  = isPhar();
        if ('' === \trim($path)) {
            return [];
        }

        if ($isPhar) {
            $path = \Phar::running()."/$path";
        }

        if (!isDirectory($path) && isFile($path)) {
            require_once($path);
            return [$path];
        } else {
            $logger->warning("It looks like the given library path `$path` does not exist, continuing without loading it.");
            return [];
        }

        try {
            $directory = new RecursiveDirectoryIterator($path);
        } catch (Throwable) {
            Bootstrap::kill("Path \"$path\" is not a valid directory or file to load.".\PHP_EOL);
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
     * @return mixed
     */
    public static function run(
        Closure|ReflectionFunction $function,
        bool $touch = true,
    ):mixed {
        if ($function instanceof Closure) {
            $reflection = new ReflectionFunction($function);
        } else {
            $reflection = $function;
            $function   = $reflection->getClosure();
        }

        if ($touch) {
            self::touch($function);
        }

        $arguments = Container::dependencies($reflection);
        return async($function, ...$arguments)->await();
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
        } else if ('object' === $className || 'stdClass' === $className) {
            return (object)[];
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
                $service->onClassMount($reflection, $instance, DependenciesOptions::create(
                    ids: [],
                    overwrites:[],
                    provides: [],
                    fallbacks: [],
                    defaultArguments: [],
                    context: false,
                ));
            }
        }
            
        self::entry($instance, $reflection->getMethods());
            
        return $instance;
    }
}
