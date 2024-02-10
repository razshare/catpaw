<?php
namespace CatPaw\Ast;

readonly class Block {
    public function __construct(
        public string $name,
        public string $body,
        public array $rules,
        public false|Block $parent,
    ) {
    }
}
