<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;

use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use ReflectionParameter;

#[Attribute]
class Option implements AttributeInterface {
    use CoreAttributeDefinition;

    private static bool $initialized = false;
    private static array $cache      = [];
    private array $options           = [];

    public function __construct(private string $name) {
        global $argv;

        if (!self::$initialized) {
            self::$initialized = true;
            foreach ($argv as $i => $value) {
                if (0 === $i || !str_starts_with($value, '-')) {
                    continue;
                }
                $this->options[] = $value;
            }
        }
    }

    private function findOptionByName(string $name):string {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        foreach ($this->options as $i => $value) {
            if (str_starts_with($value, $name)) {
                return self::$cache[$name] = trim(substr($value, strlen($name)));
            }
        }
        return self::$cache[$name] = '';
    }

    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        /** @var string|int|bool|float $value */
        /** @var false $context */
        $option = $this->findOptionByName($this->name);
        $value  = $option?$option:$reflection->getDefaultValue();
    }
}