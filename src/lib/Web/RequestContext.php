<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

readonly class RequestContext {
    /**
     * @param  string              $key
     * @param  Route               $route
     * @param  Server              $server
     * @param  Request             $request
     * @param  Response            $response
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
        Response $response,
        array $requestQueries,
        array $requestPathParameters,
        false|array $badRequestEntries,
    ):self {
        return new self(
            key: $key,
            route: $route,
            server: $server,
            request: $request,
            response: $response,
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
        public Response $response,
        public array $requestQueries,
        public array $requestPathParameters,
        public false|array $badRequestEntries,
    ) {
    }
}
