<?php

namespace CatPaw\Web;

use Throwable;

class QueryItem {
    public function __construct(
        private readonly false|string $value,
    ) {
    }

    public function text():false|string {
        return $this->value;
    }

    /**
     * @return false|int
     */
    public function int():false|int {
        try {
            if (is_numeric($this->value)) {
                return (int)$this->value;
            } else {
                return false;
            }
        } catch(Throwable) {
            return false;
        }
    }


    /**
     * @return bool
     */
    public function bool():bool {
        try {
            return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
        } catch(Throwable) {
            return false;
        }
    }

    /**
     * @return false|float
     */
    public function float():false|float {
        try {
            if (is_numeric($this->value)) {
                return (float)$this->value;
            } else {
                return false;
            }
        } catch(Throwable) {
            return false;
        }
    }
}
