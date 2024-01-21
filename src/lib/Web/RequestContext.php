<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;

readonly class RequestContext {
    /**
     * @param  string              $key
     * @param  Route               $route
     * @param  Server              $server
     * @param  Request             $request
     * @param  array<string>       $requestQueries
     * @param  array<string>       $requestPathParameters
     * @param  false|array<string> $badRequestEntries
     * @return RequestContext
     */
    public static function create(
        string $key,
        Route $route,
        Server $server,
        Request $request,
        array $requestQueries,
        array $requestPathParameters,
        false|array $badRequestEntries,
    ):self {
        return new self(
            key: $key,
            route: $route,
            server: $server,
            request: $request,
            requestQueries: $requestQueries,
            requestPathParameters: $requestPathParameters,
            badRequestEntries: $badRequestEntries,
        );
    }

    private function __construct(
        public string $key,
        public Route $route,
        public Server $server,
        public Request $request,
        public array $requestQueries,
        public array $requestPathParameters,
        public false|array $badRequestEntries,
    ) {
    }
}
