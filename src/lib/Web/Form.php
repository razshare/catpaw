<?php

namespace CatPaw\Web;

use ArrayObject;

class Form extends ArrayObject {
    public static function create() : self {
        return new self();
    }

    private function __construct() {
    }
}