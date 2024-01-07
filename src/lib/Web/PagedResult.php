<?php

namespace CatPaw\Web;

class PagedResult {
    public static function of(
        array $result,
        false|Page $page = false,
        false|Order $order = false,
    ):self {
        return new self(
            page: $page,
            order: $order,
            result: $result,
        );
    }

    private function __construct(
        public false|Page $page,
        public false|Order $order,
        public array $result,
    ) {
    }
}