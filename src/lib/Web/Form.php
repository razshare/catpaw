<?php

namespace CatPaw\Web;

use ArrayObject;

// @phpstan-ignore-next-line
class Form extends ArrayObject {
    public static function create() : self {
        return new self();
    }

    private function __construct() {
    }
}
