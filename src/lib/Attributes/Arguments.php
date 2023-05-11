<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;

use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Utilities\ReflectionTypeManager;
use ReflectionParameter;

#[Attribute]
class Arguments implements AttributeInterface {
    use CoreAttributeDefinition;

    private static bool $initialized = false;
    private static array $cache      = [];


    public function __construct() {
        self::init();
    }

    public static function init() {
        global $argv;
        if (!self::$initialized) {
            self::$initialized = true;
            foreach ($argv as $i => $value) {
                if (0 === $i || str_starts_with($value, '-')) {
                    continue;
                }
                self::$cache[] = $value;
            }
        }
    }

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, mixed $context) {
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