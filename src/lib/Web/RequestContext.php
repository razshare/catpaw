<?php

namespace CatPaw\Web;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Request;

class RequestContext {
    /** @var array<ResponseCookie> */
    public array $cookies = [];

    /**
     *
     * @param  string              $key
     * @param  Route               $route
     * @param  Request             $request
     * @param  array<string>       $requestQueries
     * @param  array<string>       $requestPathParameters
     * @param  false|array<string> $badRequestEntries
     * @return void
     */
    public function __construct(
        public readonly string $key,
        public readonly Route $route,
        public readonly Request $request,
        public readonly array $requestQueries,
        public readonly array $requestPathParameters,
        public readonly false|array $badRequestEntries,
    ) {
    }
}
