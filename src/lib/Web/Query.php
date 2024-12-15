<?php

namespace CatPaw\Web;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use Throwable;

class Query {
    public function __construct(
        private readonly string $value,
    ) {
    }

    public function text():string {
        return $this->value;
    }

    /**
     * @return Result<int>
     */
    public function int():Result {
        try {
            if (is_numeric($this->value)) {
                return ok((int)$this->value);
            } else {
                return error('Body was expected to be numeric (int), but non numeric value has been provided instead `'.$this->value.'`');
            }
        } catch(Throwable $error) {
            return error($error);
        }
    }


    /**
     * @return Result<bool>
     */
    public function bool():Result {
        try {
            return ok(filter_var($this->value, FILTER_VALIDATE_BOOLEAN));
        } catch(Throwable $error) {
            return error($error);
        }
    }

    /**
     * @return Result<float>
     */
    public function float():Result {
        try {
            if (is_numeric($this->value)) {
                return ok((float)$this->value);
            } else {
                return error('Body was expected to be numeric (float), but non numeric value has been provided instead `'.$this->value.'`');
            }
        } catch(Throwable $error) {
            return error($error);
        }
    }
}
