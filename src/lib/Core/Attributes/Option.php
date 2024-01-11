<?php
namespace CatPaw\Core\Attributes;

use Attribute;
use CatPaw\Core\Bootstrap;
use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;

use CatPaw\Core\Interfaces\OptionDocumentation;
use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;
use ReflectionParameter;


#[Attribute]
class Option implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;
    
    private static bool $initialized = false;
    private static array $cache      = [];
    /** @var array<int,string> */
    private static array $options = [];
    /** @var array<bool> */
    private static array $exists = [];
    /** @var array<string,OptionDocumentation> */
    private static array $linuxManual = [];

    /**
     * @param string $name
     * @param string $example
     * @param string $description
     * @internal
     */
    public function __construct(
        private readonly string $name,
        private readonly string $example = '',
        private readonly string $description = '',
    ) {
        if (!str_starts_with($name, '-')) {
            Bootstrap::kill("Options must start with `-`, received `$name`.");
        }
        self::init();
    }

    /**
     * @inheritDoc
     * @internal 
     */
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        if (isset(self::$cache[$this->name])) {
            $value = self::$cache[$this->name];
            return ok();
        }


        /** @var string|int|bool|float $value */
        
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
        return ok();
    }

    /**
     * Check if a cli option exists.
     * @param  string $option
     * @return bool
     */
    public static function exists(string $option): bool {
        self::init();
        if (isset(self::$exists[$option])) {
            return true;
        }
        return self::$exists[$option] = in_array($option, self::$options);
    }

    private static function init(): void {
        global $argv;
        $index          = 0;
        $listingOptions = false;
        if (!self::$initialized) {
            self::$initialized = true;
            foreach ($argv as $i => $value) {
                // $value = trim($value);
                if (
                    !$listingOptions
                    && (0 !== $i && str_starts_with($value, '-'))
                ) {
                    $listingOptions = true;
                }
                
                if (!$listingOptions) {
                    continue;
                }

                if (!str_starts_with($value, '-')) {
                    self::$options[$index - 1] .= " $value";
                } else {
                    self::$options[$index] = $value;
                    $index++;
                }
            }
        }
    }

    /**
     * @return string|null
     */
    private function extract():?string {
        $name = $this->name;
        foreach (self::$options as $value) {
            if (
                str_starts_with($value, "$name ")
                || str_starts_with($value, "$name\"")
                || str_starts_with($value, "$name'")
                || str_starts_with($value, "$name=")
                || $value === $name
                || (
                    !str_starts_with($value, '--')
                    && str_starts_with($value, $name)
                )
            ) {
                return trim(substr($value, strlen($name)));
            }
        }
        return null;
    }

    /**
     * Find the value of the option.
     * @param  string     $className
     * @param  bool       $allowsNullValue
     * @param  bool       $allowsDefaultValue
     * @param  mixed|null $defaultValue
     * @param  bool       $allowsBoolean
     * @param  bool       $allowsTrue
     * @param  bool       $allowsFalse
     * @return mixed
     */
    public function findValue(
        string $className = 'string',
        bool $allowsNullValue = false,
        bool $allowsDefaultValue = false,
        mixed $defaultValue = null,
        bool $allowsBoolean = false,
        bool $allowsTrue = false,
        bool $allowsFalse = false,
    ):mixed {
        if (isset(self::$cache[$this->name])) {
            return self::$cache[$this->name];
        }

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
                    $value = preg_replace('/"/', '"', $value);
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

        return self::$cache[$this->name] = $value;
    }
}