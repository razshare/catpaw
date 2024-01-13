<?php

namespace CatPaw\Core;

use function Amp\File\isDirectory;
use function Amp\File\isFile;
use Attribute;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\Attributes\Singleton;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;
use CatPaw\Core\Interfaces\StorageInterface;
use CatPaw\Store\Readable;
use CatPaw\Store\Writable;
use Closure;
use Phar;
use Psr\Log\LoggerInterface;
use ReflectionClass;

use ReflectionFunction;

use ReflectionMethod;
use Throwable;
use function trim;

class Container {
    private function __construct() {
    }

    private static array $singletons = [];

    public static function getSingletons():array {
        return self::$singletons;
    }

    /**
     * Check if a singleton exists in the container.
     * @param  string $className
     * @return bool
     */
    public static function has(string $className): bool {
        return Singleton::exists($className);
    }

    /**
     * Set a singleton inside the container.
     * @param  string $name
     * @param  mixed  $value
     * @return void
     */
    public static function set(string $name, mixed $value): void {
        Singleton::set($name, $value);
    }

    /**
     * Delete all singletons and cache inside the container.
     * @return void
     */
    public static function clearAll():void {
        Singleton::clearAll();
    }

    /**
     * Run the entry method of an instance of a class.
     * @param  object                  $instance
     * @param  array<ReflectionMethod> $methods  methods of the class.
     * @return Unsafe<mixed>
     */
    public static function entry(object $instance, array $methods):Unsafe {
        try {
            foreach ($methods as $method) {
                $entry = Entry::findByMethod($method)->try($error);
                if ($error) {
                    return error($error);
                }
                if ($entry) {
                    $arguments = Container::dependencies($method)->try($error);
                    if ($error) {
                        return error($error);
                    }
    
                    if ($method->isStatic()) {
                        $result = $method->invoke(null, ...$arguments);
                    } else {
                        $result = $method->invoke($instance, ...$arguments);
                    }
                    if ($result instanceof Unsafe) {
                        return $result;
                    }
                    return ok($result);
                }
            }
            return ok();
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionFunction|ReflectionMethod $reflection
     * @param  DependenciesOptions                 $options
     * @return Unsafe<array>
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
     * @param  ReflectionFunction|ReflectionMethod $reflection
     * @param  false|DependenciesOptions           $options
     * @return Unsafe<array>
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
        $results    = self::findFunctionDependencies($reflection, $options)->try($error);
        if ($error) {
            return error($error);
        }

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
                "string"           !== $type
                && "int"           !== $type
                && "float"         !== $type
                && "bool"          !== $type
                && "array"         !== $type
                && ""              !== $type
                && Writable::class !== $type
                && Readable::class !== $type
            ) {
                $instance = Container::create($type)->try($error);
                if ($error) {
                    return error($error);
                }
                $parameters[$key] = $instance;
            }

            if (0 === $numberOfAttributes) {
                if ($fallback) {
                    $parameters[$key] = $fallback($result);
                }
                continue;
            }
            try {
                foreach ($attributes as $attribute) {
                    $attributeReflectionClass = new ReflectionClass($attribute->getName());
                    $findByParameter          = $attributeReflectionClass->getMethod('findByParameter')->getClosure();
                    /** @var AttributeInterface $attributeInstanceAttempt */
                    $attributeInstance = $findByParameter($reflectionParameter)->try($error);
                    
                    if ($error) {
                        return error($error);
                    }

                    if (!$attributeInstance) {
                        if ($fallback) {
                            $parameters[$key] = $fallback($result);
                        }
                        continue;
                    }

                    if ($attributeInstance instanceof OnParameterMount) {
                        $attributeInstance->onParameterMount($reflectionParameter, $parameters[$key], $options)->try($error);
                        if ($error) {
                            return error($error);
                        }
                    }
                    
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
     * Load libraries and create singletons for the container.
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

        if (!Container::has(LoggerInterface::class)) {
            $logger = LoggerFactory::create()->try($error);
            if ($error) {
                return error($error);
            }
            Container::set(LoggerInterface::class, $logger);
        } else {
            $logger = Container::create(LoggerInterface::class)->try($error);
            if ($error) {
                return error($error);
            }
        }
        
        /** @var LoggerInterface $logger */

        $isPhar = isPhar();
        if ('' === trim($path)) {
            return ok([]);
        }

        if ($isPhar) {
            $path = Phar::running()."/$path";
        }

        if (isFile($path)) {
            require_once($path);
            return ok([$path]);
        } else if (!isDirectory($path)) {
            return ok([]);
        }
        
        $flatList = Directory::flat($path)->try($error);
        if ($error) {
            return  error($error);
        }

        $phpFileNames = [];

        foreach ($flatList as $fileName) {
            if (str_ends_with(strtolower($fileName), '.php')) {
                continue;
            }
            $phpFileNames[] = $fileName;
        }
        return ok($phpFileNames);
    }

    /**
     * Execute all attributes attached to the function.
     * 
     * Will not execute the function itself and parameter attributes will not be instantiated at all.
     * @param  Closure|ReflectionFunction $function
     * @return Unsafe<void>
     */
    public static function touch(Closure|ReflectionFunction $function): Unsafe {
        try {
            if ($function instanceof Closure) {
                $reflection = new ReflectionFunction($function);
            } else {
                $reflection = $function;
                $function   = $reflection->getClosure();
            }

            foreach ($reflection->getAttributes() as $attribute) {
                $attributeArguments = $attribute->getArguments();
                /** @var class-string $className */
                $className = $attribute->getName();
                $klass     = new ReflectionClass($className);
                /**
                 * @psalm-suppress ArgumentTypeCoercion
                 */
                $entry  = self::findEntryMethod($klass);
                $object = $klass->newInstance(...$attributeArguments);
                if ($entry) {
                    $arguments = Container::dependencies($entry)->try($error);
                    if ($error) {
                        return error($error);
                    }
                    $entry->invoke($object, ...$arguments);
                }
            }
            return ok();
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * Inject dependencies into a function and invoke it.
     * @template T
     * @param  Closure|ReflectionFunction $function
     * @param  bool                       $touch    if true, Container::touch will be 
     *                                              called automatically on the function
     * @return Unsafe<T>
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

            $arguments = Container::dependencies($reflection)->try($error);
            if ($error) {
                return error($error);
            }

            $result = $function(...$arguments);
            
            if ($result instanceof Unsafe) {
                return $result;
            }

            return ok($result);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * 
     * @param  ReflectionClass        $reflectionClass
     * @return ReflectionMethod|false
     */
    private static function findEntryMethod(ReflectionClass $reflectionClass): ReflectionMethod|false {
        foreach ($reflectionClass->getMethods() as $method) {
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
     * @template T
     * @param  class-string<T> $className full name of the class.
     * @return Unsafe<T>
     */
    public static function create(string $className):Unsafe {
        if ('callable' === $className) {
            return error("Cannot instantiate callables.");
        } else if ('object' === $className || 'stdClass' === $className) {
            return ok((object)[]);
        }
        

        if (Singleton::exists($className)) {
            return ok(Singleton::get($className));
        }

        try {
            $reflection = new ReflectionClass($className);
        } catch(Throwable $e) {
            return error($e);
        }

        if ($reflection->isInterface()) {
            return error("Cannot instantiate $className because it's an interface.");
        }

        if (AttributeResolver::issetClassAttribute($reflection, Attribute::class)) {
            return error("Cannot instantiate $className because it's meant to be an attribute.");
        }
        
        /** @var Singleton $singleton */
        $singleton = Singleton::findByClass($reflection)->try($error);
        if ($error) {
            return error($error);
        }

        /** @var Service $service */
        $service = Service::findByClass($reflection)->try($error);
        if ($error) {
            return error($error);
        }

        $constructor = $reflection->getConstructor() ?? false;

        if ($constructor) {
            $dependencies = self::dependencies($constructor)->try($error);
            if ($error) {
                return error($error);
            }
        } else {
            $dependencies = [];
        }

        $instance = null;

        if ($singleton || $service) {
            if ($service) {
                $service->onClassInstantiation($reflection, $instance, $dependencies)->try($error);
                if ($error) {
                    return error($error);
                }
            }
        } else {
            return error("Cannot instantiate $className because it's not marked as a service or a singleton.");
        }
        
        if (!$instance) {
            return error("Instance of $className is null.");
        }

        self::entry($instance, $reflection->getMethods())->try($error);
        
        if ($error) {
            return error($error);
        }
            
        return ok($instance);
    }
}
