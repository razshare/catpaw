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
        $index     = 0;
        $prev_dash = false;
        if (!self::$initialized) {
            self::$initialized = true;
            foreach ($argv as $i => $value) {
                $value = trim($value);
                if (0 === $i) {
                } else if (str_starts_with($value, '-')) {
                    self::$options[$index] = $value;
                    $index++;
                    $prev_dash = true;
                } else if ($prev_dash) {
                    $value = preg_replace('/"/', "\\\"", $value);
                    self::$options[$index - 1] .= " \"$value\"";
                    $prev_dash = false;
                } else {
                    self::$options[$index] = $value;
                    $index++;
                    $prev_dash = false;
                }
            }
        }
    }

    private function extract():?string {
        $name = $this->name;
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }
        foreach (self::$options as $i => $value) {
            if (
                str_starts_with($value, "$name ")
                || str_starts_with($value, "$name\"")
                || str_starts_with($value, "$name'")
                || str_starts_with($value, "$name=")
                || $value === $name
                || (
                    substr($value, 0, 2) !== '--'
                    && str_starts_with($value, $name)
                )
            ) {
                return self::$cache[$name] = trim(substr($value, strlen($name)));
            }
        }
        return self::$cache[$name] = null;
    }

    public function findValue(
        string $type = 'string',
        bool $allowsNullValue = false,
        bool $allowsDefaultValue = false,
        mixed $defaultValue = null,
        bool $allowsBoolean = false,
        bool $allowsTrue = false,
        bool $allowsFalse = false,
    ):mixed {
        $option = $this->extract();

        if (null !== $option) {
            if ('' === $option) {
                $value = match ($type) {
                    "string" => $allowsBoolean || $allowsTrue?true:'',
                    "int"    => $allowsBoolean || $allowsTrue?true:1,
                    "float"  => $allowsBoolean || $allowsTrue?true:(float)1,
                    "double" => $allowsBoolean || $allowsTrue?true:(double)1,
                    default  => $allowsBoolean || $allowsTrue?true:($allowsDefaultValue?$defaultValue:true),
                };
            } else {
                if (preg_match('/^\s*=?\s*"(.*)(?<!\\\)"\s*/U', $option, $groups) && count($groups) >= 2) {
                    $value = $groups[1] ?? $option;
                    $value = preg_replace('/\\\"/', '"', $value);
                } else if (preg_match('/^\s*=?\s*\'(.*)(?<!\\\)\'\s*/U', $option, $groups) && count($groups) >= 2) {
                    $value = $groups[1] ?? $option;
                    $value = preg_replace('/\\\\\'/', '\'', $value);
                } else if (preg_match('/^\s*=?\s*(.+)\s*$/U', $option, $groups) && count($groups) >= 2) {
                    $value = $groups[1] ?? $option;
                } else if (preg_match('/^\s*=\s*(.+)?\s*$/U', $option, $groups) && count($groups) >= 2) {
                    $value = $groups[1] ?? $option;
                } else {
                    $value = null;
                }

                if (str_starts_with($value, '=') && str_starts_with($option, '=')) {
                    $value = substr($value, 1);
                }

                $value = match ($type) {
                    "string" => $value,
                    "int"    => (int)$value,
                    "float"  => (float)$value,
                    "double" => (double)$value,
                    default  => null === $value?($allowsDefaultValue?$defaultValue:$value):$value,
                };
            }
        } else {
            if ($allowsNullValue) {
                if ($allowsDefaultValue) {
                    $value = $defaultValue;
                } else {
                    $value = null;
                }
            } else {
                if ($allowsDefaultValue) {
                    $value = $defaultValue;
                } else {
                    $value = match ($type) {
                        "string" => $allowsBoolean || $allowsFalse?false:(string)$option,
                        "int"    => $allowsBoolean || $allowsFalse?false:(int)$option,
                        "float"  => $allowsBoolean || $allowsFalse?false:(float)$option,
                        "double" => $allowsBoolean || $allowsFalse?false:(double)$option,
                        default  => $allowsBoolean || $allowsFalse?false:null,
                    };
                }
            }
        }

        return $value;
    }

    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        /** @var string|int|bool|float $value */
        /** @var false $context */
        $type = ReflectionTypeManager::unwrap($reflection)?->getName() ?? 'bool';

        $rtype = $reflection->getType();

        $types = [$type];

        if ($rtype instanceof ReflectionUnionType || $rtype instanceof ReflectionIntersectionType) {
            $types = $rtype->getTypes();
            foreach ($types as $i => $t) {
                /**
                 * @psalm-suppress PossiblyUndefinedMethod
                 */
                $types[] = $t->getName();
            }
        }

        $allowsBoolean      = in_array('bool', $types);
        $allowsTrue         = in_array('true', $types);
        $allowsFalse        = in_array('false', $types);
        $allowsNullValue    = $reflection->allowsNull();
        $allowsDefaultValue = $reflection->isDefaultValueAvailable();
        $defaultValue       = $allowsDefaultValue?$reflection->getDefaultValue():null;

        $value = $this->findValue(
            type: $type,
            allowsNullValue: $allowsNullValue,
            allowsDefaultValue: $allowsDefaultValue,
            defaultValue: $defaultValue,
            allowsBoolean: $allowsBoolean,
            allowsTrue: $allowsTrue,
            allowsFalse: $allowsFalse,
        );
    }
}