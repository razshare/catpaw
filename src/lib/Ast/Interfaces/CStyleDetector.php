<?php
namespace CatPaw\Ast\Interfaces;

use CatPaw\Ast\Block;
use CatPaw\Core\Unsafe;

interface CStyleDetector {
    /**
     *
     * @param  Block        $block
     * @param  int          $depth
     * @return Unsafe<void>
     */
    public function onBlock(Block $block, int $depth):Unsafe;

    /**
     *
     * @param  string       $global
     * @return Unsafe<void>
     */
    public function onGlobal(string $global):Unsafe;
}
