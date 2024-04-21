<?php
namespace CatPaw\Ast;

class Block {
    /** @var array<Block> $children */
    private array $children = [];
    /**
     *
     * @param string      $name
     * @param string      $body
     * @param array       $rules
     * @param false|Block $parent
     * @param int         $depth
     */
    public function __construct(
        public readonly string $name,
        public readonly string $body,
        public readonly array $rules,
        public readonly false|Block $parent,
        public readonly int $depth,
    ) {
    }

    /**
     * @return array<Block>
     */
    public function getChildren():array {
        return $this->children;
    }

    public function addChild(Block $block) {
        $this->children[] = $block;
    }
}
