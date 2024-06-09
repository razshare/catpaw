<?php

namespace CatPaw\Web;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Request;

class RequestContext {
    /**
     * @param  string              $key
     * @param  Route               $route
     * @param  Request             $request
     * @param  array<string>       $requestQueries
     * @param  array<string>       $requestPathParameters
     * @param  false|array<string> $badRequestEntries
     * @return RequestContext
     */
    public static function create(
        string $key,
        Route $route,
        Request $request,
        array $requestQueries,
        array $requestPathParameters,
        false|array $badRequestEntries,
    ):self {
        return new self(
            key: $key,
            route: $route,
            request: $request,
            requestQueries: $requestQueries,
            requestPathParameters: $requestPathParameters,
            badRequestEntries: $badRequestEntries,
        );
    }

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
    private function __construct(
        public readonly string $key,
        public readonly Route $route,
        public readonly Request $request,
        public readonly array $requestQueries,
        public readonly array $requestPathParameters,
        public readonly false|array $badRequestEntries,
    ) {
    }
}
