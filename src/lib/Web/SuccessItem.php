<?php
namespace CatPaw\Web;

readonly class SuccessItem {
    public static function create(
        mixed $data,
        string $message,
        int $status,
    ):self {
        return new self(
            data: $data,
            message: $message,
            status: $status,
        );
    }

    public string $type;
    private function __construct(
        public mixed $data,
        public string $message,
        public int $status,
    ) {
        $this->type = 'item';
    }
}
