<?php
namespace CatPaw\Core\Attributes;

use Attribute;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnClassInstantiation;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;

use ReflectionClass;
use Throwable;

/**
 * Attach this attribute to a class and catpaw will treat it as a singleton.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
class Singleton implements AttributeInterface, OnClassInstantiation {
    use CoreAttributeDefinition;

    public function __construct() {
    }

    
    /** @var array<string,mixed> */
    private static array $cache = [];
    
    /**
     * 
     * @return array<string,mixed>
     */
    public static function getAll():array {
        return self::$cache;
    }

    /**
     * Clear all cached singletons.\
     * Next time you create a new instance the cache will miss.
     * @return void
     * @internal
     */
    public static function clear():void {
        self::$cache = [];
    }

    /**
     * Manually cache the instance of a class.\
     * Next time you try create an instance of given class the cache will hit.
     * @param  string $className
     * @param  mixed  $value
     * @return void
     * @internal
     */
    public static function set(string $className, mixed $value):void {
        self::$cache[$className] = $value;
    }

    public static function unset(string $name):void {
        unset(self::$cache[$name]);
    }

    /**
     * Check if a given class is cached.
     * @param  string $className
     * @return bool
     * @internal
     */
    public static function exists(string $className):bool {
        return isset(self::$cache[$className]);
    }

    /**
     * Get the instance of a given class.\
     * The created instance is cached, which means all classes are singletons.
     * @param  string $className
     * @return mixed
     * @internal
     */
    public static function get(string $className):mixed {
        return self::$cache[$className] ?? false;
    }

    /**
     * Invoked whenever the instance is created.
     * @param  ReflectionClass<object> $reflection
     * @param  mixed                   $instance
     * @param  array<int,mixed>        $dependencies
     * @return Unsafe<None>
     * @internal
     */
    public function onClassInstantiation(ReflectionClass $reflection, mixed &$instance, array $dependencies): Unsafe {
        try {
            $className               = $reflection->getName();
            $instance                = new $className(...$dependencies);
            self::$cache[$className] = $instance;
        } catch(Throwable $e) {
            return error($e);
        }
        return ok();
    }
}
