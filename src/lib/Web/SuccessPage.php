<?php
namespace CatPaw\Web;

readonly class SuccessPage {
    public string $type;
    public function __construct(
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
