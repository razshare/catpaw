<?php
namespace CatPaw\Ast\Interfaces;

use CatPaw\Ast\Block;

interface CStyleDetector {
    public function on_block(Block $block);
    public function on_global(string $global);
}
