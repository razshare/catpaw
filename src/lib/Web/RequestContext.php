<?php

namespace CatPaw\Web;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

readonly class RequestContext {
    /**
     * @param string            $key
     * @param Route             $route
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array<string>     $requestQueryStrings
     * @param array<string>     $requestPathParameters
     */
    public static function create(
        string $key,
        Route $route,
        Server $server,
        RequestInterface $request,
        ResponseInterface $response,
        array $requestQueryStrings,
        array $requestPathParameters,
    ):self {
        return new self(
            key: $key,
            route: $route,
            server: $server,
            request: $request,
            response: $response,
            requestQueryStrings: $requestQueryStrings,
            requestPathParameters: $requestPathParameters,
        );
    }
        
    private function __construct(
        public string $key,
        public Route $route,
        public Server $server,
        public RequestInterface $request,
        public ResponseInterface $response,
        public array $requestQueryStrings,
        public array $requestPathParameters,
    ) {
    }
}
