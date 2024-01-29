<?php
namespace CatPaw\Core\Attributes;

use Attribute;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;

use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;

use ReflectionParameter;

#[Attribute(flags: Attribute::TARGET_PARAMETER)]
class Arguments implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;

    private static bool $initialized = false;
    private static array $cache      = [];

    public function __construct() {
        self::init();
    }

    /**
     * @return void
     */
    public static function init(): void {
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

    /**
     * @inheritDoc
     * @internal
     */
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        /** @var array<string|int|bool|float> $value */
        /** @var false $context */

        $wrap = ReflectionTypeManager::wrap($reflection, 'array');

        $className = $wrap->getClassName();

        $value = match ($className) {
            "bool"  => (bool)self::$cache,
            "array" => self::$cache?:(
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
