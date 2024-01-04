<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\DependenciesOptions;
use function CatPaw\error;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Interfaces\OnParameterMount;

use function CatPaw\ok;
use CatPaw\ReflectionTypeManager;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Unsafe;
use ReflectionClass;
use ReflectionMethod;

use ReflectionParameter;
use ReflectionProperty;

#[Attribute]
class Arguments implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;

    private static bool $initialized = false;
    private static array $cache      = [];


    public function __construct() {
        self::init();
    }

    public static function findByMethod(ReflectionMethod $reflectionMethod):Unsafe {
        return ok(false);
    }

    public static function findByClass(ReflectionClass $reflectionClass):Unsafe {
        return ok(false);
    }

    public static function findByProperty(ReflectionProperty $reflectionProperty):Unsafe {
        return ok(false);
    }

    public static function init() {
        global $argv;
        if (!self::$initialized) {
            self::$initialized = true;
            foreach ($argv as $i => $value) {
                if (0 === $i) {
                    continue;
                }
                if (str_starts_with($value, '-')) {
                    return;
                }
                self::$cache[] = $value;
            }
        }
    }

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        /** @var array<string|int|bool|float> $value */
        /** @var false $context */

        $wrap = ReflectionTypeManager::wrap($reflection, 'array');

        $className = $wrap->getClassName();

        $value = match ($className) {
            "bool"  => (bool)self::$cache,
            "array" => self::$cache?self::$cache:(
                $wrap->allowsFalse()?false:(
                    $wrap->allowsBoolean()?false:[]
                )
            ),
            "string" => self::$cache?join(' ', self::$cache):(
                $wrap->allowsFalse()?false:(
                    $wrap->allowsBoolean()?false:''
                )
            ),
            default => -1,
        };

        if (-1 === $value) {
            return error("Invalid type `$className` for arguments template. Valid types are `string` or `array`.");
        }

        return ok();
    }
}