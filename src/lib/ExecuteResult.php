<?php
namespace CatPaw;

class ExecuteResult implements \Stringable {
    public function __construct(
        private int $code,
        private string $output,
        private string $error,
    ) {
    }

    function getCode() : int {
        return $this->code;
    }
    function getOutput() : string {
        return $this->output;
    }
    function getError() : string {
        return $this->error;
    }

    public function __toString(): string {
        return $this->output.$this->error;
    }
}