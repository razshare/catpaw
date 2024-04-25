<?php
namespace CatPaw\Superstyle;

readonly class SuperstyleExecutorResult {
    public function __construct(
        public string $html,
        public string $css,
    ) {
    }
}
