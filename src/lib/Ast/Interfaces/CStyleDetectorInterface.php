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
     * @param  string       $global
     * @return Unsafe<None>
     */
    public function onGlobal(string $global):Unsafe;
}
