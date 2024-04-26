<?php
namespace CatPaw\Ast\Interfaces;

use CatPaw\Ast\SearchResult;

interface AstSearchInterface {
    /**
     * Search the next occurrence of `$tokens`.
     * @param  string             ...$tokens
     * @return false|SearchResult
     */
    public function next(string ...$tokens): false|SearchResult;
}