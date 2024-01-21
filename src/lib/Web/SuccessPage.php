<?php
namespace CatPaw\Web;

readonly class SuccessPage {
    public static function create(
        mixed $data,
        string $message,
        int $status,
        string $previousHref,
        string $nextHref,
        Page $previousPage,
        Page $nextPage,
    ):self {
        return new self(
            data: $data,
            message: $message,
            status: $status,
            previousHref: $previousHref,
            nextHref: $nextHref,
            previousPage: $previousPage,
            nextPage: $nextPage,
        );
    }

    public string $type;
    private function __construct(
        public mixed $data,
        public string $message,
        public int $status,
        public string $previousHref,
        public string $nextHref,
        public Page $previousPage,
        public Page $nextPage,
    ) {
        $this->type = 'page';
    }
}
