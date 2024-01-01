<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\DependenciesOptions;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\ReflectionTypeManager;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Unsafe;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

use function CatPaw\ok;

#[Attribute]
class Arguments implements AttributeInterface {
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

    public function onFunctionMount(ReflectionFunction $reflection, Closure &$value, DependenciesOptions $options):void {
    }

    public function onClassMount(ReflectionClass $reflection, mixed &$value, DependenciesOptions $options):void {
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

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):void {
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
            echo "Invalid type `$className` for arguments template.".PHP_EOL;
            echo "Valid types are `string` or `array`.".PHP_EOL;
            die(22);
        }
    }
}