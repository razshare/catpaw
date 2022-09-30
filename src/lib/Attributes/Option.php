<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Utilities\ReflectionTypeManager;
use ReflectionParameter;

#[Attribute]
class Option implements AttributeInterface {
    use CoreAttributeDefinition;

    private static bool $initialized = false;
    private static array $cache      = [];
    private static array $options    = [];

    public function __construct(private string $name) {
        global $argv;

        if (!self::$initialized) {
            self::$initialized = true;
            foreach ($argv as $i => $value) {
                if (0 === $i || !str_starts_with($value, '-')) {
                    continue;
                }
                self::$options[] = $value;
            }
        }
    }

    private function findOptionByName(string $name):?string {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        foreach (self::$options as $i => $value) {
            if (str_starts_with($value, $name)) {
                return self::$cache[$name] = substr($value, strlen($name));
            }
        }
        return self::$cache[$name] = null;
    }

    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        /** @var string|int|bool|float $value */
        /** @var false $context */
        $option = $this->findOptionByName($this->name);
        $type   = ReflectionTypeManager::unwrap($reflection)?->getName() ?? 'bool';

        if (null !== $option) {
            if ('' === $option) {
                $value = match ($type) {
                    "string" => '',
                    "int"    => 1,
                    "float"  => (float)1,
                    "double" => (double)1,
                    default  => $reflection->isDefaultValueAvailable()?$reflection->getDefaultValue():true,
                };
            } else {
                $value = match ($type) {
                    "string" => $option,
                    "int"    => (int)$option,
                    "float"  => (float)$option,
                    "double" => (double)$option,
                    default  => $reflection->isDefaultValueAvailable()?$reflection->getDefaultValue():true,
                };
            }
        } else {
            $value = match ($type) {
                "string" => (string)$option,
                "int"    => (int)$option,
                "float"  => (float)$option,
                "double" => (double)$option,
                default  => $reflection->isDefaultValueAvailable()?$reflection->getDefaultValue():false,
            };
        }
    }
}