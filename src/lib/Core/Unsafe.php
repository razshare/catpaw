<?php

namespace CatPaw\Core;

use function CatPaw\Web\failure;
use CatPaw\Web\Interfaces\ResponseModifier;
use function CatPaw\Web\success;

use Error;

/**
 * @template T
 * @package CatPaw\Core
 */
readonly class Unsafe {
    /**
     * @param T           $value
     * @param false|Error $error
     */
    public function __construct(
        public mixed $value,
        public false|Error $error
    ) {
        if ($error && !($error instanceof Error)) {
            $this->error = new Error($error);
        }
    }

    public function toResponseModifier(): ResponseModifier {
        if ($this->error) {
            return failure($this->error->getMessage());
        }

        if ($this->value instanceof ResponseModifier) {
            return $this->value;
        }

        return success($this->value);
    }

    /**
     * @param  Error $error
     * @return T
     */
    public function try(&$error = false) {
        if ($this->error) {
            $error = $this->error;
            return;
        }
        $error = false;
        return $this->value;
    }
}
