<?php
namespace Tests;

use CatPaw\Ast\Block;
use CatPaw\Ast\Interfaces\CStyleDetector;

use CatPaw\Ast\Search;
use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;

use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use PHPUnit\Framework\TestCase;

class AstTest extends TestCase {
    public function testAll() {
        Container::load(asFileName(__DIR__, '../src/lib'))->try($error);
        $this->assertFalse($error);
        anyError(function() {
            yield Container::run($this->makeSureCStyleParserWorks(...));
        })->try($error);
        $this->assertFalse($error);
    }


    private function makeSureCStyleParserWorks(): Unsafe {
        return anyError(function() {
            $search = Search::fromFile(asFileName(__DIR__, './app.scss'))->try($error) or yield $error;

            /** @var array<Block> $blocks */
            $blocks = [];

            /** @var array<string> $globals */
            $globals = [];

            $search->cStyle(new class($blocks, $globals) implements CStyleDetector {
                public function __construct(
                    private array &$blocks,
                    private array &$globals,
                ) {
                }
                public function onBlock(Block $block, int $depth):Unsafe {
                    $this->blocks[] = $block;
                    return ok();
                }

                public function onGlobal(string $global):Unsafe {
                    $this->globals[] = $global;

                    return ok();
                }
            });

            $this->assertEquals(2, count($blocks));
            $this->assertEquals(1, count($globals));

            $this->assertEquals('@import url("./button.scss")', $globals[0]);

            $block1 = $blocks[0];
            $this->assertEquals('.app.component', $block1->signature);
            $this->assertEquals(3, count($block1->rules));

            $block2 = $blocks[1];
            $this->assertEquals('.test.component', $block2->signature);
            $this->assertEquals(1, count($block2->rules));
        });
    }
}
