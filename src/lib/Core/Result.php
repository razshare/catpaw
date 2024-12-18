<?php

namespace CatPaw\Core;

use function CatPaw\Web\failure;
use CatPaw\Web\Interfaces\ResponseModifier;
use function CatPaw\Web\success;

use Error;
use Psr\Log\LoggerInterface;

/**
 * @template T
 * @package CatPaw\Core
 */
readonly class Result {
    /**
     * @param T          $value
     * @param null|Error $error
     */
    public function __construct(
        public mixed $value,
        public null|Error $error
    ) {
    }

    public function toResponseModifier():ResponseModifier {
        if ($this->error) {
            return failure($this->error->getMessage());
        }

        if ($this->value instanceof ResponseModifier) {
            return $this->value;
        }

        return success($this->value);
    }

    public function logError() {
        static $logger = false;
        static $first  = true;

        if (!$this->error) {
            return;
        }

        if ($first) {
            $first = false;
            if (!$logger) {
                $logger = Container::get(LoggerInterface::class)->unwrap($error);
            }
        }

        if (!$logger) {
            /** @var false|LoggerInterface $logger */
            $logger->error($this->error->getMessage());
        } else {
            echo $this->error->getMessage();
        }
    }

    /**
     *
     * @param  Error $error
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
     * @deprecated in favor of `unwrap()`.
     * @return T
     */
    public function try() {
        if ($this->error) {
            throw $this->error;
        }
        return $this->value;
    }
}
