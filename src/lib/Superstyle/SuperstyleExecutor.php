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

    /**
     *
     * @param  string                           $text
     * @param  array<string>                    $delimiters
     * @return Unsafe<SuperstyleExecutorResult>
     */
    private function unwrapString(string $text, array $delimiters): Unsafe {
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
                    if ('"' === $current) {
                        if ('\\' === $previous) {
                            $actualString = substr($actualString, 0, -1).$previous;
                        } else {
                            $actualString .= $previous;
                            // @phpstan-ignore-next-line
                            return ok($actualString);
                        }
                        continue;
                    }
                    $actualString .= $previous;
                    continue;
                }

                if ('"' === $current) {
                    $readingString = true;
                }
            }
        }

        return error("Invalid string found `$text`.");
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
     *
     * @param  array<string,mixed>              $parameters
     * @return Unsafe<SuperstyleExecutorResult>
     */
    public function execute(array $parameters = []): Unsafe {
        $signature = $this->block->signature;

        $attributesAndCleanSignature = $this->separateAttributesFromSignature($signature);
        $attributes                  = $attributesAndCleanSignature->attributes;
        $cleanSignature              = $attributesAndCleanSignature->cleanSignature;

        $this->validateSignature($cleanSignature)->try($error);
        if ($error) {
            return error($error);
        }

        $name    = $this->findNameFromSignature($cleanSignature);
        $classes = $this->findClassesFromSignature($cleanSignature);
        $class   = match ($classes) {
            ""      => "",
            default => " class=\"$classes\"",
        };
        $rules   = "";
        $content = "";

        foreach ($this->block->rules as $rule) {
            if (preg_match('/^content:(.*)/', $rule, $matches)) {
                $result = StringExpansion::variable(
                    content   : $matches[1] ?? "",
                    parameters: $parameters,
                )->try($error);

                if ($error) {
                    return error($error);
                }

                $actualString = $this->unwrapString(trim($result), ['"', "'"])->try($error);
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

        foreach ($this->block->getChildren() as $childBlock) {
            $executor = new SuperstyleExecutor($childBlock);
            $result   = $executor->execute($parameters)->try($error);
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
}


readonly class AttributesAndCleanSignature {
    public function __construct(
        public string $attributes,
        public string $cleanSignature,
    ) {
    }
}
