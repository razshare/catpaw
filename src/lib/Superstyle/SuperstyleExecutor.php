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
     * @param  SuperstyleExecutor       $executor
     * @param  string                   $name
     * @param  string                   $attributes
     * @param  string                   $signature
     * @param  string                   $cleanSignature
     * @return Unsafe<SuperstyleResult>
     */
    private static function createResult(SuperstyleExecutor $executor, string $name, string $attributes, string $signature, string $cleanSignature): Unsafe {
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

        $previousSignature = '';
        $innerHtml         = '';
        $innerCss          = '';
        $ignoreCss         = false;

        foreach ($executor->block->children as $childBlock) {
            if (!$childBlock->rules || $previousSignature === $childBlock->signature) {
                $ignoreCss = true;
            } else {
                $meaningfulRulesCounter = 0;
                foreach ($childBlock->rules as $rule) {
                    if (!str_starts_with($rule, 'content:')) {
                        $meaningfulRulesCounter++;
                        break;
                    }
                }
                $ignoreCss = 0 === $meaningfulRulesCounter;
            }

            $previousSignature = $childBlock->signature;

            $executorLocal = new SuperstyleExecutor($childBlock);
            $result        = $executorLocal->execute()->unwrap($error);
            if ($error) {
                return error($error);
            }
            $innerHtml .= $result->markup;
            if (!$ignoreCss) {
                $innerCss .= $result->style;
            }
        }

        return ok(
            new SuperstyleResult(
                markup: "<$name$attributes>$content$innerHtml</$name>",
                style : "$signature { $rules $innerCss }",
            )
        );
    }


    /**
     * @return Unsafe<SuperstyleResult>
     */
    public function execute(): Unsafe {
        $signature = $this->block->signature;

        $resolvedSignature = ResolvedSignature::resolve($signature)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $attributes     = $resolvedSignature->attributes;
        $cleanSignature = $resolvedSignature->tagName;

        $this->validateSignature($cleanSignature)->unwrap($error);
        if ($error) {
            return ok(
                new SuperstyleResult(
                    markup: '',
                    style: "{$this->block->signature}{{$this->block->body}}",
                )
            );
        }

        $name = $this->findNameFromSignature($cleanSignature);

        return self::createResult(
            executor      : $this,
            name          : $name,
            attributes    : $attributes,
            signature     : $signature,
            cleanSignature: $cleanSignature,
        );
    }
}