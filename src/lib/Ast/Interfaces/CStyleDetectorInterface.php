<?php
namespace CatPaw\Ast\Interfaces;

use CatPaw\Ast\Block;
use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

interface CStyleDetectorInterface {
    /**
     *
     * @param  Block        $block
     * @param  int          $depth
     * @return Unsafe<None>
     */
    public function onBlock(Block $block, int $depth):Unsafe;

    /**
     *
     * @param  Block        $block Owner of the rule.\
     *                             If set to `false`, then the rule is global.
     * @param  string       $rule
     * @return Unsafe<None>
     */
    public function onRule(false|Block $block, string $rule):Unsafe;
}
