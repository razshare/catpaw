<?php
namespace CatPaw\Web;

readonly class PathParametersWrapper {
    /**
     *
     * @param  bool                  $ok
     * @param  false|array<string>   $parameters
     * @param  false|array<string>   $badRequestEntries
     * @return PathParametersWrapper
     */
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

    /**
     *
     * @param  bool                $ok
     * @param  false|array<string> $parameters
     * @param  false|array<string> $badRequestEntries
     * @return void
     */
    private function __construct(
        public bool $ok,
        public false|array $parameters,
        public false|array $badRequestEntries,
    ) {
    }
}
