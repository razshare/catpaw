<?php
namespace CatPaw\Attributes;

use Attribute;
use function CatPaw\error;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Interfaces\OnClassInstantiation;

use function CatPaw\ok;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Unsafe;

use ReflectionClass;
use Throwable;

/**
 * Attach this attribute to a class.
 *
 * Catpaw will treat it as a singleton.
 */
#[Attribute]
class Singleton implements AttributeInterface, OnClassInstantiation {
    use CoreAttributeDefinition;

    private static array $cache = [];

    public static function clearAll():void {
        self::$cache = [];
    }

    public static function set(string $className, mixed $value):void {
        self::$cache[$className] = $value;
    }

    public static function exists(string $className):bool {
        return isset(self::$cache[$className]);
    }

    public static function get(string $className):mixed {
        return self::$cache[$className] ?? false;
    }

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