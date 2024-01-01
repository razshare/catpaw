<?php

namespace CatPaw;

use Attribute;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Service;
use CatPaw\Attributes\Singleton;
use CatPaw\Interfaces\StorageInterface;
use Closure;
use Psr\Log\LoggerInterface;
use function React\Async\async;
use React\Promise\Promise;
use RecursiveDirectoryIterator;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

use Throwable;

class Container {
    private function __construct() {
    }

    private static array $singletons = [];

    public static function getSingletons():array {
        return self::$singletons;
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
     * Delete all singletons and cache inside the container.
     * @return void
     */
    public static function clearAll():void {
        self::$singletons = [];
    }

    /**
     * Run the entry method of an instance of a class.
     * @param  object                  $instance
     * @param  array<ReflectionMethod> $methods  methods of the class.
     * @return Unsafe<void>
     */
    public static function entry(object $instance, array $methods):Unsafe {
        try {
            foreach ($methods as $method) {
                $entry = Entry::findByMethod($method);
                if ($entry->error) {
                    return error($entry->error);
                }
                if ($entry->value) {
                    $arguments = Container::dependencies($method);
                    if ($arguments->error) {
                        return error($arguments->error);
                    }
    
                    if ($method->isStatic()) {
                        $method->invoke(null, ...$arguments->value);
                    } else {
                        $method->invoke($instance, ...$arguments->value);
                    }
                    break;
                }
            }
            return ok();
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @return Unsafe<array<mixed>>
     */
    private static function findFunctionDependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        DependenciesOptions $options,
    ):Unsafe {
        try {
            $defaultArguments     = $options->defaultArguments;
            $reflectionParameters = $reflection->getParameters();
            $items                = [];
            foreach ($reflectionParameters as $key => $reflectionParameter) {
                $defaultArgument = $defaultArguments[$key] ?? null;
                $isOptional      = $reflectionParameter->isOptional();
                $defaultValue    = $reflectionParameter->isDefaultValueAvailable()?$reflectionParameter->getDefaultValue():null;
                $type            = ReflectionTypeManager::unwrap($reflectionParameter)?->getName() ?: '';
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

            return ok($items);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @return Unsafe<array<mixed>>
     */
    public static function dependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        false|DependenciesOptions $options = false,
    ):Unsafe {
        if (!$options) {
            $options = DependenciesOptions::create(
                key: '',
                overwrites: [],
                provides: [],
                fallbacks: [],
                defaultArguments: [],
                context: false,
            );
        }
        $parameters = [];
        $results    = self::findFunctionDependencies($reflection, $options);
        if ($results->error) {
            return error($results->error);
        }

        foreach ($results->value as $key => $result) {
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
                $instance = Container::create($type);
                if ($instance->error) {
                    return error($instance->error);
                }
                $parameters[$key] = $instance->value;
            }

            if (0 === $numberOfAttributes) {
                if ($fallback) {
                    $parameters[$key] = $fallback($result);
                }
                continue;
            }
            try {
                foreach ($attributes as $attribute) {
                    $attributeClass    = new ReflectionClass($attribute->getName());
                    $findByParameter   = $attributeClass->getMethod('findByParameter')->getClosure();
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
                    if ($parameters[$key] instanceof StorageInterface) {
                        $parameters[$key] = &$parameters[$key]->getStorage();
                    }
                }
            } catch(Throwable $e) {
                return error($e);
            }
        }

        return ok($parameters);
    }

    /**
     * Load libraries and create default singletons for the container.
     * @param  string                $path
     * @param  bool                  $append if true, the resulting singletons will be appended to the container, otherwise all the other singletons will be cleared before scanning.
     * @return Unsafe<array<string>> list of files scanned.
     */
    public static function load(
        string $path,
        bool $append = false,
    ):Unsafe {
        if (!$append) {
            Container::clearAll();
        }

        if (!isset(self::$singletons[LoggerInterface::class])) {
            $logger = LoggerFactory::create();
            Container::set(LoggerInterface::class, $logger);
        } else {
            $result = Container::create(LoggerInterface::class);
            if ($result->error) {
                return error($result->error);
            }
            $logger = $result->value;
        }
        
        /** @var LoggerInterface $logger */

        $isPhar = isPhar();
        if ('' === \trim($path)) {
            return ok([]);
        }

        if ($isPhar) {
            $path = \Phar::running()."/$path";
        }

        if (File::isFile($path)) {
            require_once($path);
            return [$path];
        } else if (!File::isDirectory($path)) {
            return ok([]);
        }

        try {
            $directory = new RecursiveDirectoryIterator($path);
        } catch (Throwable) {
            return error("Path \"$path\" is not a valid directory or file to load.");
        }

        return File::listFilesRecursively($directory, '/^.+\.php$/i');
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
                if ($arguments->error) {
                    return error($arguments->error);
                }
                $entry->invoke($object, ...$arguments->value);
            }
        }
    }

    /**
     * Inject dependencies into a function and invoke it.
     * @template T
     * @param  Closure|ReflectionFunction $function
     * @param  bool                       $touch    if true, Container::touch will be 
     *                                              called automatically on the function
     * @return Unsafe<Promise<T>>
     */
    public static function run(
        Closure|ReflectionFunction $function,
        bool $touch = true,
    ):Unsafe {
        try {
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
            if ($arguments->error) {
                return error($arguments->error);
            }

            return ok(async($function)(...$arguments->value));
        } catch(Throwable $e) {
            return error($e);
        }
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
     * @param  string         $className full name of the class.
     * @return Unsafe<object>
     */
    public static function create(string $className):Unsafe {
        if ('callable' === $className) {
            return error("Cannot instantiate callables.");
        } else if ('object' === $className || 'stdClass' === $className) {
            return (object)[];
        }
        

        if (self::$singletons[$className] ?? false) {
            return ok(self::$singletons[$className]);
        }

        $reflection = new ReflectionClass($className);

        if ($reflection->isInterface()) {
            return error("Cannot instantiate $className because it's an interface.");
        }

        if (AttributeResolver::issetClassAttribute($reflection, Attribute::class)) {
            return error("Cannot instantiate $className because it's meant to be an attribute.");
        }

        if (count($reflection->getAttributes()) === 0) {
            return error("Cannot instantiate $className because it's not marked as a service or a singleton.");
        }

        $service = Service::findByClass($reflection);
        if ($service->error) {
            return error($service->error);
        }
        $singleton = Singleton::findByClass($reflection);
        if ($singleton->error) {
            return error($singleton->error);
        }

        $constructor = $reflection->getConstructor() ?? false;

        if ($constructor) {
            $arguments = self::dependencies($constructor);
            if ($arguments->error) {
                return error($arguments->error);
            }
            $instance = new $className(...$arguments->value);
        } else {
            $instance = new $className();
        }


        if ($singleton->value || $service->value) {
            self::$singletons[$className] = $instance;
            if ($service->value) {
                $service->value->onClassMount($reflection, $instance, DependenciesOptions::create(
                    key: '',
                    overwrites:[],
                    provides: [],
                    fallbacks: [],
                    defaultArguments: [],
                    context: false,
                ));
            }
        }

        if ($error = self::entry($instance, $reflection->getMethods())->error) {
            return error($error);
        }
            
        return ok($instance);
    }
}
