<?php
namespace CatPaw\Core;

use Stringable;

readonly class ExecuteResult implements Stringable {
    /**
     * @param int    $code
     * @param string $output
     * @param string $error
     */
    public function __construct(
        private int $code,
        private string $output,
        private string $error,
    ) {
    }

    /**
     * @return int
     */
    function getCode() : int {
        return $this->code;
    }
    
    /**
     * @return string
     */
    function getOutput() : string {
        return $this->output;
    }
    
    /**
     * @return string
     */
    function getError() : string {
        return $this->error;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->output.$this->error;
    }
}