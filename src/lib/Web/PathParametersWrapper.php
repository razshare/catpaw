<?php
namespace CatPaw\Web;

readonly class PathParametersWrapper {
    public static function create(
        bool $ok,
        false|array $parameters,
        false|array $badRequestEntries,
    ):self {
        return new self(
            ok: $ok,
            parameters: $parameters,
            badRequestEntries: $badRequestEntries,
        );
    }
    private function __construct(
        public bool $ok,
        public false|array $parameters,
        public false|array $badRequestEntries,
    ) {
    }
}