<?php
namespace CatPaw\Core;

use Stringable;

readonly class None implements Stringable {
    public function __toString() {
        return '';
    }
}
