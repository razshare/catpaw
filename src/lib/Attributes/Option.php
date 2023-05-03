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
    /** @var array<int,string> */
    private static array $options = [];
    /** @var array<bool> */
    private static array $exists = [];
    /** @var array<string,\CatPaw\Attributes\Interfaces\OptionDocumentation> */
    private static array $linuxManual = [];

    public function __construct(
        private string $name,
        private string $example = '',
        private string $description = '',
    ) {
        if (!str_starts_with($name, '-')) {
            echo "Options must start with `-`, received `$name`.".PHP_EOL;
            die(22);
        }
        self::$linuxManual[$name] = (object)[
            "example"     => $example,
            "description" => $description,
        ];
        self::init();
    }

    public static function renderLinuxManual():string {
        $result = '';
        foreach (self::$linuxManual as $option => $guide) {
            // $guide->
        }

        return $result;
    }

    public static function exists(string $option) {
        self::init();
        if (isset(self::$exists[$option])) {
            return true;
        }
        return self::$exists[$option] = in_array($option, self::$options);
    }

    public static function init() {
        global $argv;
        $index = 0;
        if (!self::$initialized) {
            self::$initialized = true;
            foreach ($argv as $i => $value) {
                $value = trim($value);
                if (0 === $i || !str_starts_with($value, '-')) {
                    continue;
                }
                
                self::$options[$index] = $value;
                $index++;
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
        string $className = 'string',
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
                $value = match ($className) {
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

                $value = match ($className) {
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
                    $value = match ($className) {
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

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        /** @var string|int|bool|float $value */
        /** @var false $context */
        $wrapper = ReflectionTypeManager::wrap($reflection);

        $value = $this->findValue(
            className: $wrapper->getClassName(),
            allowsNullValue: $wrapper->allowsNullValue(),
            allowsDefaultValue: $wrapper->allowsDefaultValue(),
            defaultValue: $wrapper->getDefaultValue(),
            allowsBoolean: $wrapper->allowsBoolean(),
            allowsTrue: $wrapper->allowsTrue(),
            allowsFalse: $wrapper->allowsFalse(),
        );
    }
}