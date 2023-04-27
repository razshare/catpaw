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
    private static array $blueprints = [];

    private const PATTERN_PARAM = '/^\{[0-9]*\w+\}$/';

    /** @var callable */
    private mixed $act;

    public function __construct(private string $template = '') {
        self::init();
        $this->act = fn (callable $action) => $action(...self::$blueprints[$this->template]);
        if ($pieces = preg_split('/\s+/i', $this->template)) {
            foreach ($pieces as $key => $value) {
                if (preg_match(self::PATTERN_PARAM, $value)) {
                    self::$blueprints[$template][] = self::$cache[$key] ?? false;
                } else if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
                    echo "Invalid placeholder `$value` for template `$template`".PHP_EOL;
                    die(22);
                }
            }
        }
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

        if ($this->template && ReflectionTypeManager::unwrap($reflection)->getName() === 'array') {
            $value = self::$blueprints[$this->template];
        } else if ($this->template && ReflectionTypeManager::unwrap($reflection)->getName() === 'callable') {
            $value = $this->act;
        } else {
            $value = self::$cache;
        }
    }
}