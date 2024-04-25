<?php

namespace CatPaw\Web;

class PagedResult {
    /**
     *
     * @param  array<mixed> $result
     * @param  false|Page   $page
     * @param  false|Order  $order
     * @return PagedResult
     */
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

    /**
     *
     * @param  false|Page   $page
     * @param  false|Order  $order
     * @param  array<mixed> $result
     * @return void
     */
    private function __construct(
        public false|Page $page,
        public false|Order $order,
        public array $result,
    ) {
    }
}
