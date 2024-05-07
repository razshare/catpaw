<?php
namespace CatPaw\Superstyle;

class SuperstyleResult {
    public function __construct(
        public readonly string $markup,
        public readonly string $style,
    ) {
    }
}
