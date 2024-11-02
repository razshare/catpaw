<?php
namespace CatPaw\Core;

use function filter_var;

class StringExpansion {
    /**
     * @param  string              $content
     * @param  array<string,mixed> $parameters
     * @return Result<string>
     */
    public static function variable(string $content, array $parameters):Result {
        $result      = '';
        $stack       = StringStack::of($content);
        $occurrences = $stack->expect("{", "}");
        $tokenFound  = false;

        for ($occurrences->rewind(); $occurrences->valid(); $occurrences->next()) {
            [$prev, $token] = $occurrences->current();

            if ('{' === $token) {
                $tokenFound = true;
            }

            if ($tokenFound && '}' === $token) {
                if (!isset($parameters[$prev])) {
                    return error("Parameter `$prev` it not valid.");
                }
                $result .= $parameters[$prev];
                $tokenFound = false;
                continue;
            }

            $result .= $prev;
        }

        return ok($result);
    }


    public const OP_NONE               = 0;
    public const OP_AND                = 1;
    public const OP_OR                 = 2;
    public const OP_GT                 = 3;
    public const OP_GTE                = 4;
    public const OP_LT                 = 5;
    public const OP_LTE                = 6;
    public const OP_EQUALS_ISH         = 7;
    public const OP_EQUALS             = 8;
    public const OP_NOT_EQUALS_ISH     = 9;
    public const OP_NOT_EQUALS         = 10;
    public const OP_LEFT_BINARY_SHIFT  = 11;
    public const OP_RIGHT_BINARY_SHIFT = 12;
    public const OP_BINARY_AND         = 13;
    public const OP_BINARY_OR          = 14;
    public const OP_BINARY_XOR         = 15;
    public const OP_BINARY_NOT         = 16;

    /**
     * @param  string                                             $content
     * @param  false|callable(string,int,string):Result<bool|int> $validator
     * @return Result<bool>
     */
    public static function linearCondition(string $content, false|callable $validator = false):Result {
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


                if ($validator) {
                    $previousValue = $validator($previousValue, $previousOperator, $value)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                } else {
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
                        // @phpstan-ignore-next-line
                        default => false
                    };
                }

                
                if (!$token) {
                    return ok((bool)$previousValue);
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

        return ok(-1 === $previousValue ? false : (bool)$previousValue);
    }

    /**
     * @param  string                                             $content
     * @param  int                                                $depth
     * @param  false|callable(string,int,string):Result<bool|int> $validator
     * @return Result<bool>
     */
    public static function groupCondition(string $content, int $depth = 0, false|callable $validator = false):Result {
        if ($depth > 10) {
            return error("Too many nested groups in the condition (max 10).");
        }

        // Il numero di parentesi aperte deve essere uguale al numero di parentesi chiuse
        $numberOfOpenParenthesis   = preg_match_all('/\(/', $content);
        $numberOfClosedParenthesis = preg_match_all('/\)/', $content);

        if (false === $numberOfOpenParenthesis || false === $numberOfClosedParenthesis || $numberOfOpenParenthesis !== $numberOfClosedParenthesis) {
            return error("The number of opened and closed parenthesis must match (opened:$numberOfOpenParenthesis, closed:$numberOfClosedParenthesis).");
        }

        $content     = strtolower(trim($content));
        $stack       = StringStack::of($content);
        $occurrences = $stack->expect("(", ")");


        $result = '';

        for ($occurrences->rewind(); $occurrences->valid(); $occurrences->next()) {
            [$previous, $token] = $occurrences->current();
            if (false === $token && '' === $result) {
                return self::linearCondition($content, $validator);
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
                    $chunk = self::linearCondition($previous, $validator)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $result .= ($chunk ? 'true' : 'false').')';
                }
            }
        }

        $count = 0;
        do {
            $result = preg_replace(['/\((false)\)/', '/\((true)\)/'], ['false', 'true'], $result, -1, $count);
        } while ($count > 0);


        if (str_contains($result, '(') && str_contains($result, ')')) {
            return self::groupCondition($result, ++$depth, $validator);
        }

        return self::linearCondition($result, $validator);
    }

    /**
     * @param  string              $content
     * @param  array<string,mixed> $parameters
     * @return Result<bool>
     */
    public static function condition(string $content, array $parameters):Result {
        $variable = self::variable($content, $parameters)->unwrap($error);
        if ($error) {
            return error($error);
        }
        return self::groupCondition($variable);
    }

    /**
     * @param  string                                     $content
     * @param  array<string,mixed>                        $parameters
     * @param  callable(mixed,int,mixed):Result<bool|int> $validator
     * @return Result<bool>
     */
    public static function conditionCustomized(string $content, array $parameters, callable $validator):Result {
        $variable = self::variable($content, $parameters)->unwrap($error);
        if ($error) {
            return error($error);
        }
        return self::groupCondition($variable, 0, $validator);
    }


    /**
     * Parse text surrounded by `$delimiters` as a string.
     * @param  string         $text
     * @param  array<string>  $delimiters
     * @return Result<string>
     */
    public static function delimit(string $text, array $delimiters):Result {
        foreach ($delimiters as $delimiter) {
            $stack         = StringStack::of($text);
            $occurrences   = $stack->expect($delimiter, '\\');
            $readingString = false;
            $actualString  = '';
            for ($occurrences->rewind(); $occurrences->valid(); $occurrences->next()) {
                /**
                 * @var string $previous
                 * @var string $current
                 */
                [$previous, $current] = $occurrences->current();

                if ($readingString) {
                    if ($delimiter === $current) {
                        if ('\\' === $previous) {
                            $actualString = substr($actualString, 0, -1).$previous;
                        } else {
                            $actualString .= $previous;
                            return ok($actualString);
                        }
                        continue;
                    }
                    $actualString .= $previous;
                    continue;
                }

                if ($delimiter === $current) {
                    $readingString = true;
                }
            }
        }

        return error("Invalid string found `$text`.");
    }
}
