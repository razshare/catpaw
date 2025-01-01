<?php
namespace CatPaw\Web;

readonly class PathParametersWrapper {
    /**
     *
     * @param  bool                $ok
     * @param  false|array<string> $parameters
     * @param  false|array<string> $badRequestEntries
     * @return void
     */
    public function __construct(
        public bool $ok,
        public false|array $parameters,
        public false|array $badRequestEntries,
    ) {
    }
}
