<?php
namespace CatPaw\Core;

class KebabCase {
    /**
     * Given a `snake_case` string, convert it into `kebab-case`.
     * @param  string $value
     * @return string
     */
    public static function fromSnakeCase(string $value):string {
        return str_ireplace('_', '-', $value);
    }
    /**
     * Given a `camelCase` string, convert it into `kebab-case`.
     * @param  string $value
     * @return string
     */
    public static function fromCamelCase(string $value):string {
        return self::fromPascalCase($value);
    }

    /**
     * Given a `PascalCase` string, convert it into `kebab-case`.
     * @param  string $value
     * @return string
     */
    public static function fromPascalCase(string $value):string {
        $result = '';
        $stack  = StringStack::of($value);
        $tokens = array_unique(mb_str_split(mb_strtoupper($value)));
        $items  = $stack->expect(...$tokens);

        for ($items->rewind();$items->valid();$items->next()) {
            [$before, $token] = $items->current();
            
            $result .= $before;

            if (false !== $token) {
                if ('' !== $before) {
                    $result .= '-';
                }
                $result .= strtolower($token);
            }
        }

        if (str_starts_with($result, '-')) {
            return substr($result, 1);
        }

        return $result;
    }

    /**
     * Given a any string, convert it into `kebab-case`.
     * @param  string $value
     * @return string
     */
    public static function fromAny(string $value):string {
        return KebabCase::fromPascalCase(KebabCase::fromSnakeCase($value));
    }
}