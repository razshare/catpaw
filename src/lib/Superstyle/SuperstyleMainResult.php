<?php
namespace CatPaw\Superstyle;

class SuperstyleMainResult {
    public function __construct(
        public readonly string $markup,
        public readonly string $style,
    ) {
    }
}
