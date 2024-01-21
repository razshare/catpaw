<?php
namespace CatPaw\Web;

readonly class ErrorItem {
    public static function create(
        string $message,
        int $status,
    ):self {
        return new self(
            message: $message,
            status: $status,
        );
    }

    public string $type;
    private function __construct(
        public string $message,
        public int $status,
    ) {
        $this->type = 'error';
    }
}
