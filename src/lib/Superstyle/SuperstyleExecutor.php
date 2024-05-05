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
     * @return Unsafe<SuperstyleExecutorResult>
     */
    private static function createSuperstyleExecutorResult(SuperstyleExecutor $executor, string $name, string $attributes, string $signature, string $cleanSignature): Unsafe {
        $classes = $executor->findClassesFromSignature($cleanSignature);
        $class   = match ($classes) {
            ""      => "",
            default => " class=\"$classes\"",
        };
        $rules   = "";
        $content = "";

        foreach ($executor->block->rules as $rule) {
            if (preg_match('/^content:(.*)/', $rule, $matches)) {
                $trimmed = trim($matches[1]);
                if (
                    (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"'))
                    || (str_starts_with($trimmed, '\'') && str_ends_with($trimmed, '\''))
                ) {
                    $actualString = StringExpansion::delimit(trim($matches[1]), ['"', "'"])->unwrap($error);
                    if ($error) {
                        return error($error);
                    }

                    $content .= $actualString;
                    continue;
                }
                return error("Rule `content` must always use a string value, non string value received `$rule`.");
            } else {
                $rules .= "$rule;";
            }
        }


        $innerHtml = '';
        $innerCss  = '';

        $previousSignature = '';
        $ignoreCss         = false;

        foreach ($executor->block->children as $childBlock) {
            if ($childBlock->isServerInject) {
                $innerHtml .= $childBlock->body;
                $previousSignature = $childBlock->signature;
                if (
                    str_starts_with($childBlock->body, '{{#each')
                    || str_starts_with($childBlock->body, '{{/each')
                ) {
                    continue;
                }
                $innerCss .= $childBlock->body;
                continue;
            }

            if ($previousSignature === $childBlock->signature) {
                $ignoreCss = true;
            } else {
                $previousSignature = $childBlock->signature;
            }

            $executorLocal = new SuperstyleExecutor($childBlock);
            $result        = $executorLocal->execute()->unwrap($error);
            if ($error) {
                return error($error);
            }
            $innerHtml .= $result->html;
            if (!$ignoreCss) {
                $innerCss .= $result->css;
            }
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
     * @return Unsafe<SuperstyleExecutorResult>
     */
    public function execute(): Unsafe {
        $signature = $this->block->signature;

        $attributesAndCleanSignature = $this->separateAttributesFromSignature($signature);
        $attributes                  = $attributesAndCleanSignature->attributes;
        $cleanSignature              = $attributesAndCleanSignature->cleanSignature;

        $this->validateSignature($cleanSignature)->unwrap($error);
        if ($error) {
            return ok(new SuperstyleExecutorResult(html: '', css: "{$this->block->signature}{{$this->block->body}}"));
        }

        $name = $this->findNameFromSignature($cleanSignature);

        return self::createSuperstyleExecutorResult(
            executor      : $this,
            name          : $name,
            attributes    : $attributes,
            signature     : $signature,
            cleanSignature: $cleanSignature,
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
