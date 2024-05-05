<?php
namespace CatPaw\Superstyle\Services;

use CatPaw\Ast\Block;
use CatPaw\Ast\CStyleAstDetector;
use CatPaw\Ast\Interfaces\CStyleDetectorInterface;
use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Superstyle\SuperstyleExecutor;
use CatPaw\Superstyle\SuperstyleExecutorResult;
use DOMDocument;
use DOMElement;

#[Service]
class SuperstyleService {
    /**
     *
     * @param  string                           $fileName
     * @return Unsafe<SuperstyleExecutorResult>
     */
    public  function file(string $fileName):Unsafe {
        $file = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $source = $file->readAll()->unwrap($error);

        if ($error) {
            return error($error);
        }

        return $this->source($source);
    }
    /**
     *
     * @param  string                           $source
     * @return Unsafe<SuperstyleExecutorResult>
     */
    public  function source(string $source):Unsafe {
        $dom = new DOMDocument;
        if (!$dom->loadHTML($source)) {
            return error("Could not load source.");
        }

        $nodes = $dom->getElementsByTagName("style");
        
        if (($count = $nodes->count()) > 1) {
            return error("Only one style tag is allowed in superstyle, $count found instead.");
        }

        if (!isset($nodes[0])) {
            return error("No style tag found.");
        }

        /** @var DOMElement $node */
        $node = $nodes[0];
        
        $detector = CStyleAstDetector::fromSource($node->textContent);

        /** @var array<string> */
        $globals = [];
        /** @var null|Block $main */
        $main = null;

        $detector->detect(new class(globals: $globals, main: $main) implements CStyleDetectorInterface {
            private int $counter = 0;

            /**
             *
             * @param  array<string> $globals
             * @param  null|Block    $main
             * @return void
             */
            public function __construct(
                // @phpstan-ignore-next-line
                private array &$globals,
                private null|Block &$main,
            ) {
            }

            /**
             *
             * @param  Block        $block
             * @param  int          $depth
             * @return Unsafe<None>
             */
            public function onBlock(Block $block, int $depth):Unsafe {
                if (0 === $block->depth) {
                    if ('main' === $block->signature) {
                        if ($this->main) {
                            return error("Error multiple top level main blocks are not allowed.");
                        }
                        $this->main = $block;
                    }

                    if ($block->isServerInject) {
                        $this->globals["inject-$this->counter"] = $block->body;
                        $this->counter++;
                    }
                }
                return ok();
            }
            public function onRule(false|Block $block, string $rule):Unsafe {
                if (0 === $block->depth && 'main' !== $block->signature) {
                    $this->globals[$block->signature] = "{$block->signature}{{$block->body}}";
                    return ok();
                }
                return ok();
            }
        });

        if (!$main) {
            return error("A top level main block is required in order to render an application.");
        }

        $executor = new SuperstyleExecutor(block: $main);
        $result   = $executor->execute()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $result->withGlobals(join($globals));

        return ok($result);
    }
}