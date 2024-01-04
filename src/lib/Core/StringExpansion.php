<?php

namespace CatPaw;

use Exception;

use function filter_var;

class StringExpansion {
    /**
     * @return Unsafe<string>
     */
    public static function variable(string $content, array $parameters): Unsafe {
        $result = '';

        $stack       = StringStack::of($content);
        $occurrences = $stack->expect("{{", "}}");

        $tokenFound = false;

        for ($occurrences->rewind(); $occurrences->valid(); $occurrences->next()) {
            [$prev, $token] = $occurrences->current();

            if ('{{' === $token) {
                $tokenFound = true;
            }

            if ($tokenFound && '}}' === $token) {
                if (!isset($parameters[$prev])) {
                    return error("Parameter $prev it not valid.");
                }
                $result .= $parameters[$prev];
                $tokenFound = false;
                continue;
            }

            $result .= $prev;
        }

        return ok($result);
    }


    private const OP_NONE               = 0;
    private const OP_AND                = 1;
    private const OP_OR                 = 2;
    private const OP_GT                 = 3;
    private const OP_GTE                = 4;
    private const OP_LT                 = 5;
    private const OP_LTE                = 6;
    private const OP_EQUALS_ISH         = 7;
    private const OP_EQUALS             = 8;
    private const OP_NOT_EQUALS_ISH     = 9;
    private const OP_NOT_EQUALS         = 10;
    private const OP_LEFT_BINARY_SHIFT  = 11;
    private const OP_RIGHT_BINARY_SHIFT = 12;
    private const OP_BINARY_AND         = 13;
    private const OP_BINARY_OR          = 14;
    private const OP_BINARY_XOR         = 15;
    private const OP_BINARY_NOT         = 16;

    public static function linearCondition(string $content): bool {
        $previousValue = false;

        $stack       = StringStack::of($content);
        $occurrences = $stack->expect("&&", "||", "<<", ">>", "&", "|", "^", ">", ">=", "<", "<=", "===", "!==", "==", "!=", "=");

        $previousOperator = self::OP_NONE;

        for ($occurrences->rewind(); $occurrences->valid(); $occurrences->next()) {
            [$text, $token] = $occurrences->current();

            while ($occurrences->valid()) {
                $occurrences->next();
                [$followingText, $followingToken] = $occurrences->current();
                if (!$followingText) {
                    if (match ($token.$followingToken) {
                        "<<", ">>", ">=", "<=", "===", "==", "!==", "!=", "&&", "||", => true,
                        default => false
                    }) {
                        $token .= $followingToken;
                    } else {
                        $occurrences->prev();
                        break;
                    }
                } else {
                    $occurrences->prev();
                    break;
                }
            }

            if (self::OP_NONE !== $previousOperator) {
                $value = match ($previousOperator) {
                    self::OP_AND, self::OP_OR => filter_var(trim($text), FILTER_VALIDATE_BOOL),
                    self::OP_GT,
                    self::OP_GTE,
                    self::OP_LT,
                    self::OP_LTE => filter_var(trim($text), FILTER_VALIDATE_FLOAT),
                    default      => trim($text)
                };


                $previousValue = match ($previousOperator) {
                    self::OP_AND                => $previousValue && $value,
                    self::OP_OR                 => $previousValue || $value,
                    self::OP_GT                 => $previousValue > $value,
                    self::OP_GTE                => $previousValue >= $value,
                    self::OP_LT                 => $previousValue < $value,
                    self::OP_LTE                => $previousValue <= $value,
                    self::OP_EQUALS_ISH         => $previousValue == $value,
                    self::OP_EQUALS             => $previousValue === $value,
                    self::OP_NOT_EQUALS_ISH     => $previousValue != $value,
                    self::OP_NOT_EQUALS         => $previousValue !== $value,
                    self::OP_LEFT_BINARY_SHIFT  => $previousValue << $value,
                    self::OP_RIGHT_BINARY_SHIFT => $previousValue >> $value,
                    self::OP_BINARY_AND         => $previousValue & $value,
                    self::OP_BINARY_OR          => $previousValue | $value,
                    self::OP_BINARY_XOR         => $previousValue ^ $value,
                    default                     => false
                };
                if (!$token) {
                    return $previousValue;
                }
            } else {
                if (!$token) {
                    $previousValue = filter_var(trim($text), FILTER_VALIDATE_BOOLEAN);
                } else {
                    $previousValue = match ($token) {
                        '&&', '||' => filter_var(trim($text), FILTER_VALIDATE_BOOLEAN),
                        '>', '>=', '<', '<=' => filter_var(trim($text), FILTER_VALIDATE_FLOAT),
                        default => trim($text)
                    };
                }
            }


            $previousOperator = match ($token) {
                '&&'    => self::OP_AND,
                '||'    => self::OP_OR,
                '>'     => self::OP_GT,
                '>='    => self::OP_GTE,
                '<'     => self::OP_LT,
                '<='    => self::OP_LTE,
                '=='    => self::OP_EQUALS_ISH,
                '==='   => self::OP_EQUALS,
                '!='    => self::OP_NOT_EQUALS_ISH,
                '!=='   => self::OP_NOT_EQUALS,
                '<<'    => self::OP_LEFT_BINARY_SHIFT,
                '>>'    => self::OP_RIGHT_BINARY_SHIFT,
                '&'     => self::OP_BINARY_AND,
                '|'     => self::OP_BINARY_OR,
                '^'     => self::OP_BINARY_XOR,
                default => self::OP_NONE
            };
        }

        return -1 === $previousValue ? false : $previousValue;
    }

    /**
     * @return Unsafe<bool>
     */
    public static function groupCondition(string $content, int $depth = 0): Unsafe {
        if ($depth > 10) {
            return error("Too many nested groups in the condition (max 10).");
        }

        //Il numero di parentesi aperte deve essere uguale al numero di parentesi chiuse
        $numberOfOpenParenthesis   = preg_match_all('/\(/', $content);
        $numberOfClosedParenthesis = preg_match_all('/\)/', $content);

        if (false === $numberOfOpenParenthesis || false === $numberOfClosedParenthesis || $numberOfOpenParenthesis !== $numberOfClosedParenthesis) {
            return error("The number of opened and closed parenthesis must match (opened:$numberOfOpenParenthesis, closedd:$numberOfClosedParenthesis).");
        }

        $content     = strtolower(trim($content));
        $stack       = StringStack::of($content);
        $occurrences = $stack->expect("(", ")");


        $result = '';

        for ($occurrences->rewind(); $occurrences->valid(); $occurrences->next()) {
            [$previous, $token] = $occurrences->current();
            if (false === $token && '' === $result) {
                return ok(self::linearCondition($content));
            }
            if (false === $previous) {
                continue;
            }
            $previous = trim($previous);
            if ('' === $previous) {
                $result .= $token;
                continue;
            }
            if ("(" === $token) {
                $result .= "$previous(";
            } else {
                if (")" === $token) {
                    $result .= (self::linearCondition($previous) ? 'true' : 'false').')';
                }
            }
        }

        $count = 0;
        do {
            $result = preg_replace(['/\((false)\)/', '/\((true)\)/'], ['false', 'true'], $result, -1, $count);
        } while ($count > 0);


        if (str_contains($result, '(') && str_contains($result, ')')) {
            return self::groupCondition($result, ++$depth);
        }

        return ok(self::linearCondition($result));
    }

    /**
     * @param  string    $content
     * @param  array     $parameters
     * @throws Exception
     * @return Unsafe<bool>
     */
    public static function condition(string $content, array $parameters): Unsafe {
        $variable = self::variable($content, $parameters);
        if($variable->error){
            return error($variable->error);
        }
        return self::groupCondition($variable->value);
    }
}
