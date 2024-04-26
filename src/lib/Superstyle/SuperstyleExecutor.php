<?php

namespace CatPaw\Superstyle;

use CatPaw\Ast\Block;
use function CatPaw\Core\error;

use CatPaw\Core\None;
use function CatPaw\Core\ok;

use CatPaw\Core\StringExpansion;
use CatPaw\Core\StringStack;
use CatPaw\Core\Unsafe;

readonly class SuperstyleExecutor {
    /**
     *
     * @param  Block $block
     * @return void
     */
    public function __construct(public Block $block) {
    }


    private function separateAttributesFromSignature(string $signature): AttributesAndCleanSignature {
        $cleanSignature   = '';
        $stack            = StringStack::of($signature)->expect('[', ']');
        $readingAttribute = false;
        /** @var array<string> $attributes */
        $attributes = [];
        for ($stack->rewind(); $stack->valid(); $stack->next()) {
            /**
             * @var string       $previous
             * @var false|string $current
             */
            [$previous, $current] = $stack->current();

            if (false === $current) {
                $cleanSignature .= $previous;
                continue;
            }

            if ($readingAttribute && ']' === $current) {
                $attributes[] = $previous;
                continue;
            }

            if ('[' === $current) {
                $cleanSignature .= $previous;
                $readingAttribute = true;
            }
        }

        if (!$attributes) {
            return new AttributesAndCleanSignature(attributes: '', cleanSignature: $cleanSignature);
        }

        return new AttributesAndCleanSignature(attributes: ' '.join(' ', $attributes), cleanSignature: $cleanSignature);
    }

    private function findClassesFromSignature(string $signature): string {
        $stack            = StringStack::of($signature)->expect('.');
        $readingAttribute = false;
        /** @var array<string> $attributes */
        $attributes = [];
        for ($stack->rewind(); $stack->valid(); $stack->next()) {
            /**
             * @var string $previous
             * @var string $current
             */
            [$previous, $current] = $stack->current();

            $validClassNameCharacter = preg_match('/^[A-z0-9-_]$/', $current);

            if ($readingAttribute && !$validClassNameCharacter) {
                $attributes[] = $previous;
                continue;
            }

            if ('.' === $current) {
                $readingAttribute = true;
            }
        }

        return join(' ', $attributes);
    }

    private function findNameFromSignature(string $signature): string {
        if (preg_match('/^([A-z0-9-_]+)/', $signature, $matches)) {
            return $matches[1];
        }
        return "div";
    }

    /**
     * @param  string       $signature
     * @return Unsafe<None>
     */
    private function validateSignature(string $signature): Unsafe {
        $stack = StringStack::of($signature)->expect('::', ':', '@', '^', '/', '(', ')');
        for ($stack->rewind(); $stack->valid(); $stack->next()) {
            /**
             * @var false|string $current
             */
            [, $current] = $stack->current();
            $outcome     = match ($current) {
                '::'    => error('Element signatures are not allowed to contain `::` when declared within main.'),
                ':'     => error('Element signatures are not allowed to contain `:` when declared within main.'),
                '@'     => error('Element signatures are not allowed to contain `@` when declared within main.'),
                '^'     => error('Element signatures are not allowed to contain `^` when declared within main.'),
                '/'     => error('Element signatures are not allowed to contain `/` when declared within main.'),
                '('     => error('Element signatures are not allowed to contain `(` when declared within main.'),
                ')'     => error('Element signatures are not allowed to contain `)` when declared within main.'),
                default => ok(),
            };
            if ($outcome->error) {
                return $outcome;
            }
        }

        if (preg_match('/^[A-z0-9-_[]]+$/', $signature)) {
            return error("Invalid element signature - `$signature`.");
        }

        return ok();
    }

    /**
     * @param  SuperstyleExecutor               $executor
     * @param  string                           $name
     * @param  string                           $attributes
     * @param  string                           $signature
     * @param  string                           $cleanSignature
     * @param  array<string,mixed>              $parameters
     * @return Unsafe<SuperstyleExecutorResult>
     */
    private static function createSuperstyleExecutorResult(SuperstyleExecutor $executor, string $name, string $attributes, string $signature, string $cleanSignature, array $parameters): Unsafe {
        $classes = $executor->findClassesFromSignature($cleanSignature);
        $class   = match ($classes) {
            ""      => "",
            default => " class=\"$classes\"",
        };
        $rules   = "";
        $content = "";

        foreach ($executor->block->rules as $rule) {
            if (preg_match('/^content:(.*)/', $rule, $matches)) {
                $result = StringExpansion::variable(
                    content   : $matches[1] ?? "",
                    parameters: $parameters,
                )->unwrap($error);

                if ($error) {
                    return error($error);
                }

                $actualString = StringExpansion::delimit(trim($result), ['"', "'"])->unwrap($error);
                if ($error) {
                    return error($error);
                }

                $content .= $actualString;
            } else {
                $rules .= "$rule;";
            }
        }


        $innerHtml = '';
        $innerCss  = '';

        foreach ($executor->block->getChildren() as $childBlock) {
            $executorLocal = new SuperstyleExecutor($childBlock);
            $result        = $executorLocal->execute($parameters)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $innerHtml .= $result->html;
            $innerCss  .= $result->css;
        }

        return ok(
            new SuperstyleExecutorResult(
                html: <<<HTML
                    <$name$class$attributes>$content$innerHtml</$name>
                    HTML,
                css : "$signature { $rules $innerCss }",
            )
        );
    }

    
    /**
     * 
     * @param  array<string>    $delimiters
     * @param  mixed            $left
     * @param  int              $operator
     * @param  mixed            $right
     * @return Unsafe<bool|int>
     */
    private static function customValidator(array $delimiters, mixed $left, int $operator, mixed $right):Unsafe {
        if (is_string($left)) {
            $leftDelimited = StringExpansion::delimit($left, $delimiters)->unwrap($error);
            if (null !== $leftDelimited) {
                $left = $leftDelimited;
            }
        }

        if (is_string($right)) {
            $rightDelimited = StringExpansion::delimit($right, $delimiters)->unwrap($error);
            if (null !== $rightDelimited) {
                $right = $rightDelimited;
            }
        }

        return ok(match ($operator) {
            StringExpansion::OP_AND                => $left && $right,
            StringExpansion::OP_OR                 => $left || $right,
            StringExpansion::OP_GT                 => $left > $right,
            StringExpansion::OP_GTE                => $left >= $right,
            StringExpansion::OP_LT                 => $left < $right,
            StringExpansion::OP_LTE                => $left <= $right,
            StringExpansion::OP_EQUALS_ISH         => $left == $right,
            StringExpansion::OP_EQUALS             => $left === $right,
            StringExpansion::OP_NOT_EQUALS_ISH     => $left != $right,
            StringExpansion::OP_NOT_EQUALS         => $left !== $right,
            StringExpansion::OP_LEFT_BINARY_SHIFT  => $left << $right,
            StringExpansion::OP_RIGHT_BINARY_SHIFT => $left >> $right,
            StringExpansion::OP_BINARY_AND         => $left & $right,
            StringExpansion::OP_BINARY_OR          => $left | $right,
            StringExpansion::OP_BINARY_XOR         => $left ^ $right,
            default                                => false,
        });
    }

    /**
     * @param  string                                                                                                        $query
     * @return callable(SuperstyleExecutor,string,string,string,string,array<string,mixed>):Unsafe<SuperstyleExecutorResult>
     */
    private static function createServerFunction(string $query): callable {
        $stack = StringStack::of($query)->expect('=', '\\', '"', '\'', ' ');
        /** @var array<string,string> $properties */
        $properties = [];

        $readingString       = false;
        $usingDoubleQuotes   = false;
        $usingSingleQuotes   = false;
        $previousIsBackslash = false;
        $propertyName        = '';
        $propertyValue       = '';
        for ($stack->rewind(); $stack->valid(); $stack->next()) {
            /**
             * @var false|string $previous
             * @var false|string $current
             */
            [$previous, $current] = $stack->current();

            if ($readingString) {
                if ('\\' === $current) {
                    $propertyValue .= $previous;
                    $previousIsBackslash = true;
                }

                if ($usingDoubleQuotes) {
                    if ('"' === $current) {
                        if ($previousIsBackslash) {
                            $propertyValue .= '\\"';
                            continue;
                        }
    
                        $readingString = false;
                        $propertyValue .= $previous;
                        $properties[trim($propertyName)] = $propertyValue;
                        $propertyValue                   = '';
                        $propertyName                    = '';
                        continue;
                    } else {
                        $propertyValue .= $previous.$current;
                        continue;
                    }
                } else if ($usingSingleQuotes) {
                    if ('\'' === $current) {
                        if ($previousIsBackslash) {
                            $propertyValue .= "\\'";
                            continue;
                        }
    
                        $readingString = false;
                        $propertyValue .= $previous;
                        $properties[trim($propertyName)] = $propertyValue;
                        $propertyValue                   = '';
                        $propertyName                    = '';
                        continue;
                    } else {
                        $propertyValue .= $previous.$current;
                        continue;
                    }
                } else {
                    $propertyValue .= $previous.$current;
                    continue;
                }
            }

            if (!$usingSingleQuotes && '"' === $current) {
                $usingDoubleQuotes = true;
                $usingSingleQuotes = false;
                $readingString     = true;
                $propertyName .= $previous;
                continue;
            } else if (!$usingDoubleQuotes && '\'' === $current) {
                $usingDoubleQuotes = false;
                $usingSingleQuotes = true;
                $readingString     = true;
                $propertyName .= $previous;
                continue;
            }

            if ('=' === $current) {
                $propertyName .= $previous;
                continue;
            }

            $propertyName .= $current;
        }

        $delimiters = match (true) {
            $usingDoubleQuotes => ["'","\""],
            $usingSingleQuotes => ["\"","'"],
            default            => ["'","\""],
        };


        return 
            /**
             * @param  SuperstyleExecutor               $executor
             * @param  string                           $name
             * @param  string                           $attributes
             * @param  string                           $signature
             * @param  string                           $cleanSignature
             * @param  array<string,mixed>              $parameters
             * @return Unsafe<SuperstyleExecutorResult>
             */
            function(
                SuperstyleExecutor $executor,
                string $name,
                string $attributes,
                string $signature,
                string $cleanSignature,
                array $parameters,
            ) use ($properties, $delimiters): Unsafe {
                return match (true) {
                    isset($properties['if']) => match (
                        StringExpansion::conditionCustomized(
                            content: $properties['if'],
                            parameters: $parameters,
                            validator: fn ($left, $operator, $right) => self::customValidator($delimiters, $left, $operator, $right)
                        )->unwrap($error)
                    ) {
                        true => self::createSuperstyleExecutorResult(
                            executor      : $executor,
                            name          : $name,
                            attributes    : $attributes,
                            signature     : $signature,
                            cleanSignature: $cleanSignature,
                            parameters    : $parameters,
                        ),
                        default => match (true) {
                            (bool)$error => error($error),
                            default      => ok(new SuperstyleExecutorResult(html: '', css: ''))
                        },
                    },
                    default => ok(new SuperstyleExecutorResult(html: '', css: '')),
                };
            };
    }

    /**
     *
     * @param  array<string,mixed>              $parameters
     * @return Unsafe<SuperstyleExecutorResult>
     */
    public function execute(array $parameters = []): Unsafe {
        $signature = $this->block->signature;

        $attributesAndCleanSignature = $this->separateAttributesFromSignature($signature);
        $attributes                  = $attributesAndCleanSignature->attributes;
        $cleanSignature              = $attributesAndCleanSignature->cleanSignature;

        $this->validateSignature($cleanSignature)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $name = $this->findNameFromSignature($cleanSignature);

        if ('server' === $name) {
            echo "is server element";
            $serverFunction = $this->createServerFunction($attributes);
            return $serverFunction(
                $this,
                $name,
                $attributes,
                $signature,
                $cleanSignature,
                $parameters,
            );
        }

        return self::createSuperstyleExecutorResult(
            executor      : $this,
            name          : $name,
            attributes    : $attributes,
            signature     : $signature,
            cleanSignature: $cleanSignature,
            parameters    : $parameters,
        );
    }
}


readonly class AttributesAndCleanSignature {
    public function __construct(
        public string $attributes,
        public string $cleanSignature,
    ) {
    }
}
