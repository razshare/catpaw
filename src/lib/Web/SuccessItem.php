<?php
namespace CatPaw\Web;

readonly class SuccessItem {
    public string $type;
    public function __construct(
        public mixed $data,
        public string $message,
        public int $status,
    ) {
        $this->type = 'item';
    }
}
