<?php
namespace CatPaw\Web;

readonly class ErrorItem {
    public string $type;
    public function __construct(
        public string $message,
        public int $status,
    ) {
        $this->type = 'error';
    }
}
