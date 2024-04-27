<?php
namespace CatPaw\Ast;

class Block {
    /**
     *
     * @param string        $signature
     * @param string        $body
     * @param array<string> $rules
     * @param false|Block   $parent
     * @param int           $depth
     * @param array<Block>  $children
     */
    public function __construct(
        public string $signature,
        public string $body,
        public array $rules,
        public false|Block $parent,
        public int $depth,
        public array $children = [],
    ) {
    }
}
