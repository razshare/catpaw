<?php
namespace CatPaw;

use Error;

/**
 * @template T
 */
class Unsafe {
    /**
     * @param T           $value
     * @param false|Error $error
     */
    public function __construct(
        public readonly mixed $value,
        public readonly false|Error $error
    ) {
        if ($error && !($error instanceof Error)) {
            $this->error = new Error($error);
        }
    }
}