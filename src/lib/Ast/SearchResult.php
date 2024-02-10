<?php
namespace CatPaw\Ast;

readonly class SearchResult {
    public function __construct(
        public string $token,
        public string $before,
    ) {
    }
}
