<?php
namespace CatPaw;

class ExecuteResult implements \Stringable {
    public function __construct(
        private int $code,
        private string $output,
        private string $error,
    ) {
    }
    public function __toString(): string {
        return $this->output.$this->error;
    }
}