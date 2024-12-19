<?php
namespace CatPaw\Core\Attributes;

use Attribute;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnClassInstantiation;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use ReflectionClass;
use Throwable;

/**
 * Provide a dependency.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
class Provider implements AttributeInterface, OnClassInstantiation {
    use CoreAttributeDefinition;
    
    /**
     * Provide a dependency.
     * @param  bool $singleton If `true` the provider will create a singleton, 
     *                         otherwise it will create a new instance each time the dependency is requested.
     * @return void
     */
    public function __construct(public readonly bool $singleton = true) {
    }

    /** @var array<string,callable> */
    private static array $functions = [];
    /**
     * Create a provider for a given name.
     * @param  string   $name
     * @param  callable $function
     * @return void
     * @internal
     */
    public static function set(string $name, callable $function):void {
        self::$functions[$name] = $function;
    }

    public static function unset(string $name):void {
        unset(self::$functions[$name]);
    }

    /**
     * Check if a given provider is set.
     * @param  string $className
     * @return bool
     * @internal
     */
    public static function isset(string $className):bool {
        return isset(self::$functions[$className]);
    }

    /**
     * Get a given provider.
     * @param  string         $className
     * @return false|callable
     * @internal
     */
    public static function get(string $className):false|callable {
        return self::$functions[$className] ?? false;
    }

    /**
     * Clear all providers.\
     * Next time you create a new instance the cache will miss.
     * @return void
     * @internal
     */
    public static function clearAll():void {
        self::$functions = [];
    }


    /** @var array<string,ReflectionClass<\stdClass>> */
    private static array $classes = [];

    /**
     * Given the name of an interface, find the name of its provider.
     * 
     * Interfaces cannot be provided directly, they need a proxy, a proper instantiable class.\
     * This method finds the class that implements the given interface, 
     * so that you can get an instance of that class from the container.\
     * This is mostly used internally, there shouldn't be any need to call this method directly.
     * @param  string                     $interfaceName Name of the interface.
     * @return Result<false|class-string> Name of the provider.
     */
    public static function findNameByInterface(string $interfaceName):Result {
        /** @var array<ReflectionClass<object>> */
        $classesWithoutAttribute = [];
        /** @var array<ReflectionClass<object>> */
        $possibleClasses      = [];
        $countPossibleClasses = 0;

        foreach (get_declared_classes() as $className) {
            if (!isset(self::$classes[$className])) {
                if (isset(self::$classes[$className])) {
                    $reflectionClass = self::$classes[$className];
                } else {
                    $reflectionClass = new ReflectionClass($className);
                }

                if ($reflectionClass->implementsInterface($interfaceName)) {
                    $attribute = Provider::findByClass($reflectionClass)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }

                    if (!$attribute) {
                        $classesWithoutAttribute[] = $reflectionClass;
                        continue;
                    }

                    $possibleClasses[] = $reflectionClass;
                    $countPossibleClasses++;
                }
            }
        }

        if ($countPossibleClasses > 1) {
            $listOfProviderClasses = '';

            foreach ($possibleClasses as $reflectionClass) {
                /** @var false|Provider $attribute */
                $attribute = Provider::findByClass($reflectionClass)->unwrap($provideError);
                if ($provideError) {
                    return error($provideError);
                }
                if (!$attribute) {
                    continue;
                }
                $className = $reflectionClass->getName();
                $listOfProviderClasses .= "$className,\n";
            }

            return error(
                <<<TEXT
                    Multiple providers found for interface `$interfaceName`, which is not allowed

                    $listOfProviderClasses
                    
                    Makes sure each interface is provided by at most 1 provider per application.\n
                    TEXT
            );
        } else if (1 === $countPossibleClasses) {
            /** @var Result<false|class-string> */
            return ok($possibleClasses[0]->getName());
        }

        if ($classesWithoutAttribute) {
            $stringifiedClassNames = join("\n", $classesWithoutAttribute);
            return error(
                <<<LOG
                    No providers found for interface `$interfaceName`, however, the following classes seem to implement `$interfaceName`

                    $stringifiedClassNames

                    Perhaps one of those classes should specify a `#[Provider]` attribute.\n
                    LOG
            );
        }

        /** @var Result<false|class-string> */
        return ok(false);
    }

    /** @var array<string,array<string>> */
    private static array $aliases = [];

    public static function withAlias(string $name, string $alias):void {
        if (!isset(self::$aliases[$name])) {
            self::$aliases[$name] = [];
        }
        self::$aliases[$name][$alias] = true;
    }

    /**
     * Invoked whenever the instance is created.
     * @param  ReflectionClass<object> $reflectionClass
     * @param  mixed                   $instance
     * @param  array<int,mixed>        $dependencies
     * @return Result<None>
     * @internal
     */
    public function onClassInstantiation(ReflectionClass $reflectionClass, mixed &$instance, array $dependencies):Result {
        try {
            $name = $reflectionClass->getName();
            if ($this->singleton) {
                $instance = new $name(...$dependencies);
                $provider = static function() use ($instance) {
                    return $instance;
                };
                self::set($name, $provider);
                if (isset(self::$aliases[$name])) {
                    foreach (self::$aliases[$name] as $alias => $_) {
                        self::set($alias, $provider);
                    }
                }
            } else {
                $instance = new $name(...$dependencies);
                self::set($name, static function() use ($name, &$dependencies) {
                    return new $name(...$dependencies);
                });
            }
        } catch(Throwable $error) {
            return error($error);
        }
        return ok();
    }
}
