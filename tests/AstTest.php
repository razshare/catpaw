<?php
namespace Tests;

use CatPaw\Ast\Block;
use CatPaw\Ast\Interfaces\CStyleDetector;

use CatPaw\Ast\Search;
use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
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

            /** @var array<Block> */
            $blocks = [];

            /** @var array<string> */
            $globals = [];

            $search->cStyle(new class($blocks, $globals) implements CStyleDetector {
                public function __construct(
                    private array &$blocks,
                    private array &$globals,
                ) {
                }
                public function on_block(Block $block):void {
                    $this->blocks[] = $block;
                }

                public function on_global(string $global):void {
                    $this->globals[] = $global;
                }
            });

            $this->assertEquals(2, count($blocks));
            $this->assertEquals(1, count($globals));

            $this->assertEquals('@import url("./button.scss")', $globals[0]);

            $block1 = $blocks[0];
            $this->assertEquals('component::app', $block1->name);
            $this->assertEquals(3, count($block1->rules));

            $block2 = $blocks[1];
            $this->assertEquals('component::test', $block2->name);
            $this->assertEquals(1, count($block2->rules));
        });
    }
}
