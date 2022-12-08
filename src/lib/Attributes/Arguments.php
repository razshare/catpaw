<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;

use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use ReflectionParameter;

#[Attribute]
class Arguments implements AttributeInterface {
    use CoreAttributeDefinition;

    private static bool $initialized = false;
    private static array $cache      = [];

    public function __construct() {
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
        $value = self::$cache;
    }
}