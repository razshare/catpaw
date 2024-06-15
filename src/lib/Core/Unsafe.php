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
     * @param T          $value
     * @param null|Error $error
     */
    public function __construct(
        public mixed $value,
        public null|Error $error
    ) {
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
     *
     * @param  null|Error $error
     * @return T
     */
    public function unwrap(&$error = null) {
        if ($this->error) {
            $error = $this->error;
            /** @var T */
            return null;
        }
        $error = null;
        return $this->value;
    }

    /**
     * Get the value or throw the error if present.
     * @throws Error
     * @return T
     */
    public function try() {
        if ($this->error) {
            throw $this->error;
        }
        return $this->value;
    }
}
