<?php
namespace CatPaw\Superstyle;

use CatPaw\Ast\Block;
use CatPaw\Ast\Interfaces\CStyleDetector;
use CatPaw\Ast\Search;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;

use CatPaw\Core\Unsafe;

class Superstyle {
    /**
     *
     * @param  string       $source
     * @return Unsafe<void>
     */
    public static function parse(string $fileName, array $parameters = []):Unsafe {
        $search = Search::fromFile($fileName)->try($error);
        if ($error) {
            return error($error);
        }

        /** @var array<string> */
        $globals = [];
        /** @var null|Block $main */
        $main = null;

        $search->cStyle(new class(globals: $globals, main: $main) implements CStyleDetector {
            /**
             *
             * @param  array<string> $globals
             * @param  null|Block    $main
             * @return void
             */
            public function __construct(
                private array &$globals,
                private null|Block &$main,
            ) {
            }

            public function onBlock(Block $block, int $depth):Unsafe {
                if (0 === $block->depth && 'main' === $block->name) {
                    if ($this->main) {
                        return error("Error multiple top level main blocks are not allowed.");
                    }
                    $this->main = $block;
                }
                return ok();
            }
            public function onGlobal(string $global):Unsafe {
                $this->globals[] = $global;
                return ok();
            }
        });

        if (!$main) {
            return error("A top level main block is required in order to render an application.");
        }

        $executor = new SuperstyleExecutor(block: $main);

        return $executor->execute($parameters);
    }
}
