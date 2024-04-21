<?php
namespace CatPaw\Superstyle;

use CatPaw\Ast\Block;
use function CatPaw\Core\error;
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
     * @param  string         $text
     * @param  array          $delimiters
     * @return Unsafe<string>
     */
    private function unwrapString(string $text, array $delimiters):Unsafe {
        foreach ($delimiters as $delimiter) {
            $stack         = StringStack::of($text);
            $occurrences   = $stack->expect($delimiter, '\\');
            $readingString = false;
            $actualString  = '';
            for ($occurrences->rewind();$occurrences->valid();$occurrences->next()) {
                /** @var string $prev */
                /** @var string $current */
                [$prev, $current] = $occurrences->current();

                if ($readingString) {
                    if ('"' === $current) {
                        if ('\\' === $prev) {
                            $actualString = substr($actualString, 0, -1).$prev;
                        } else {
                            $actualString .= $prev;
                            return ok($actualString);
                        }
                        continue;
                    }
                    $actualString .= $prev;
                    continue;
                }

                if ('"' === $current) {
                    $readingString = true;
                    continue;
                }
            }
        }

        return error("Invalid string found `$text`.");
    }

    /**
     *
     * @param  array          $parameters
     * @return Unsafe<string>
     */
    public function execute(array $parameters = []): Unsafe {
        $name       = $this->block->name;
        $attributes = "";
        $content    = "";


        foreach ($this->block->rules as $rule) {
            if (preg_match('/^content:(.*)/', $rule, $matches)) {
                $text = StringExpansion::variable(
                    content: $matches[1] ?? "",
                    parameters: $parameters,
                )->try($error);

                if ($error) {
                    return error($error);
                }

                $actualString = $this->unwrapString(trim($text), ['"',"'"])->try($error);
                if ($error) {
                    return error($error);
                }

                $content .= $actualString;
            } else if (preg_match('/^.*:(.*)/', $rule, $matches)) {
                $attributes .= ' '.$matches[1] ?? '';
            }
        }

        $inner = '';

        foreach ($this->block->getChildren() as $childBlock) {
            $executor = new SuperstyleExecutor($childBlock, $parameters);
            $text     = $executor->execute($parameters)->try($error);
            if ($error) {
                return error($error);
            }
            $inner .= $text;
        }

        return ok(
            <<<HTML
                <$name$attributes>$content$inner</$name>
                HTML
        );
    }
}
