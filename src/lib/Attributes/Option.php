<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Utilities\ReflectionTypeManager;
use ReflectionIntersectionType;
use ReflectionParameter;
use ReflectionUnionType;

#[Attribute]
class Option implements AttributeInterface {
    use CoreAttributeDefinition;

    private static bool $initialized = false;
    private static array $cache      = [];
    private static array $options    = [];

    public function __construct(private string $name) {
        global $argv;
        
        $index = 0;
        if (!self::$initialized) {
            self::$initialized = true;
            foreach ($argv as $i => $value) {
                if (0 === $i) {
                    continue;
                } else if (!str_starts_with($value, '-') && $index - 1 > 0) {
                    self::$options[$index - 1] .= " $value";
                    continue;
                }

                self::$options[$index] = $value;
                $index++;
            }
        }
    }

    private function findOptionByName(string $name):?string {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        foreach (self::$options as $i => $value) {
            if (str_starts_with($value, "$name ") || $value === $name) {
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

        $rtype = $reflection->getType();

        $types = [];

        if ($rtype instanceof ReflectionUnionType || $rtype instanceof ReflectionIntersectionType) {
            $types = $rtype->getTypes();
            foreach ($types as $i => $t) {
                if ('null' !== $t) {
                    $types[] = $t->getName();
                }
            }
        }

        $allowsBoolean = in_array('bool', $types);
        $allowsTrue    = in_array('true', $types);
        $allowsFalse   = in_array('false', $types);

        
        if (null !== $option) {
            if ('' === $option) {
                $value = match ($type) {
                    "string" => $allowsBoolean || $allowsTrue?true:'',
                    "int"    => $allowsBoolean || $allowsTrue?true:1,
                    "float"  => $allowsBoolean || $allowsTrue?true:(float)1,
                    "double" => $allowsBoolean || $allowsTrue?true:(double)1,
                    default  => $allowsBoolean || $allowsTrue?true:($reflection->isDefaultValueAvailable()?$reflection->getDefaultValue():true),
                };
            } else {
                $value = match ($type) {
                    "string" => $option,
                    "int"    => (int)$option,
                    "float"  => (float)$option,
                    "double" => (double)$option,
                    default  => $reflection->isDefaultValueAvailable()?$reflection->getDefaultValue():true,
                };
                if ($value === $option && preg_match('/((?<=\").+(?=\"))/', $value, $groups) && count($groups) >= 2) {
                    $value = $groups[1] ?? $value;
                } else if ($value === $option && preg_match('/((?<=\').+(?=\'))/', $value, $groups) && count($groups) >= 2) {
                    $value = $groups[1] ?? $value;
                }
            }
        } else {
            if ($reflection->allowsNull()) {
                $value = null;
            } else {
                $value = match ($type) {
                    "string" => $allowsBoolean || $allowsFalse?false:(string)$option,
                    "int"    => $allowsBoolean || $allowsFalse?false:(int)$option,
                    "float"  => $allowsBoolean || $allowsFalse?false:(float)$option,
                    "double" => $allowsBoolean || $allowsFalse?false:(double)$option,
                    default  => $allowsBoolean || $allowsFalse?false:($reflection->isDefaultValueAvailable()?$reflection->getDefaultValue():false),
                };
            }
        }
    }
}