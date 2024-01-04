<?php

namespace CatPaw\Web;

readonly class MatchingPath {
    public function __construct(public string $path, public array $parameters) {
    }
}