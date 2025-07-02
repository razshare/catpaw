<?php

namespace CatPaw\Core;

use function CatPaw\Web\failure;
use CatPaw\Web\Interfaces\ResponseModifierInterface;
use function CatPaw\Web\success;

use Error;
use Psr\Log\LoggerInterface;

/**
 * @template T
 * @package CatPaw\Core
 */
readonly class Result {
    /**
     * @param null|T     $value
     * @param null|Error $error
     */
    public function __construct(
        private mixed $value,
        private null|Error $error
    ) {
    }

    public function toResponseModifier():ResponseModifierInterface {
        if ($this->error) {
            return failure($this->error->getMessage());
        }

        if ($this->value instanceof ResponseModifierInterface) {
            return $this->value;
        }

        return success($this->value);
    }

    /**
     * Log the error.
     * @return void
     */
    public function logError():void {
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
     * @param  Error  $error
     * @return null|T
     */
    public function unwrap(&$error = null) {
        if ($this->error) {
            $error = $this->error;
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
