<?php
namespace CatPaw\Core\Errors;

use Error;
use Throwable;

class NoMatchError extends Error {
    public function __construct(string $message, int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    public function __toString() {
        return __CLASS__.": [{$this->code}]: {$this->message}\n";
    }
}