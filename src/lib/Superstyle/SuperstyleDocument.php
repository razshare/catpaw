<?php
namespace CatPaw\Superstyle;

readonly class SuperstyleDocument {
    public function __construct(
        public string $markup,
        public string $style,
        public string $script,
    ) {
    }
}