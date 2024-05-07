<?php
namespace CatPaw\Superstyle\Services;

use CatPaw\Ast\Block;
use CatPaw\Ast\CStyleAstDetector;
use CatPaw\Ast\Interfaces\CStyleDetectorInterface;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;

use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Superstyle\SuperstyleDocument;
use CatPaw\Superstyle\SuperstyleExecutor;
use CatPaw\Web\Services\HandlebarsService;
use DOMDocument;
use DOMElement;

#[Service]
class SuperstyleService {
    private HandlebarsService $handlebarsService;

    #[Entry] public function start(HandlebarsService $handlebarsService):void {
        $this->handlebarsService = $handlebarsService;
    }

    /**
     *
     * @param  string                     $fileName
     * @param  array<mixed>               $context
     * @return Unsafe<SuperstyleDocument>
     */
    public  function file(string $fileName, array $context = []):Unsafe {
        $file = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $source = $file->readAll()->unwrap($error);

        if ($error) {
            return error($error);
        }

        return $this->source(source:$source, context:$context, id:$fileName);
    }
    /**
     *
     * @param  string                     $source
     * @param  array<mixed>               $context
     * @param  string                     $id
     * @return Unsafe<SuperstyleDocument>
     */
    public  function source(string $source, array $context = [], string $id = ''):Unsafe {
        $resolvedSource = $this->handlebarsService->source($source, $context, $id)->unwrap($error);

        if ($error) {
            return error($error);
        }

        $dom = new DOMDocument;
        if (!$dom->loadHTML($resolvedSource)) {
            return error("Could not load source.");
        }

        $styleNodes = $dom->getElementsByTagName("style");
        
        if (($styleNodesCount = $styleNodes->count()) > 1) {
            return error("Only one style tag is allowed in superstyle, $styleNodesCount found instead.");
        }

        if (!isset($styleNodes[0])) {
            return error("No style tag found.");
        }

        /** @var DOMElement $style */
        $style = $styleNodes[0];

        $detector = CStyleAstDetector::fromSource($style->textContent);

        /** @var array<string> */
        $globals = [];
        /** @var null|Block $main */
        $main = null;

        $detector->detect(new class(globals: $globals, main: $main) implements CStyleDetectorInterface {
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

        $scriptNodes = $dom->getElementsByTagName("script");
        
        if (($scriptNodesCount = $scriptNodes->count()) > 1) {
            return error("Only one script tag is allowed in superstyle, $scriptNodesCount found instead.");
        }

        /** @var DOMElement $style */
        $script = $scriptNodes[0];

        $executor = new SuperstyleExecutor(block: $main);
        $result   = $executor->execute()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $scriptTextContent = '';
        if ($script) {
            $scriptTextContent = $script->textContent;
        }
        return ok(new SuperstyleDocument(
            markup: $result->markup,
            style: join($globals).$result->style,
            script: $scriptTextContent,
        ));
    }
}