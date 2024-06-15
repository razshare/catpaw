<?php

namespace CatPaw\Web\Implementations\RouteResolver;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Web\HttpStatus;
use CatPaw\Web\Interfaces\HttpInvokerInterface;
use CatPaw\Web\Interfaces\RouteResolverInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\PathResolver;
use CatPaw\Web\RequestContext;
use Psr\Http\Message\UriInterface;
use ReflectionFunction;
use Throwable;

#[Provider]
class SimpleRouteResolver implements RouteResolverInterface {
    /** @var array<string,PathResolver> */
    private array $cache = [];

    public function __construct(
        public readonly RouterInterface $router,
        public readonly HttpInvokerInterface $httpInvoker,
    ) {
    }

    /**
     * @param  Request                $request
     * @return Unsafe<false|Response>
     */
    public function resolve(Request $request):Unsafe {
        $requestMethod  = $request->getMethod();
        $symbolicMethod = $requestMethod;

        $routes = $this->router->findRoutesByMethod($requestMethod);


        $requestPathParameters = false;
        /** @var false|array<string> */
        $badRequestEntries = false;

        $symbolicPath = '';
        $route        = '';
        foreach ($routes as $symbolicPath => $route) {
            $key         = "$requestMethod:$symbolicPath";
            $function    = $route->function;
            $requestPath = urldecode($request->getUri()->getPath());

            if (isset($this->cache[$key])) {
                /** @var PathResolver $pathResolver */
                $pathResolver      = $this->cache[$key];
                $parametersWrapper = $pathResolver->findParametersFromPath($requestPath);
                if ($parametersWrapper->ok) {
                    if ($parametersWrapper->badRequestEntries) {
                        $badRequestEntries = $parametersWrapper->badRequestEntries;
                        break;
                    }
                    $requestPathParameters = $parametersWrapper->parameters;
                    break;
                }
                continue;
            }

            try {
                $reflectionFunction = new ReflectionFunction($function);
            } catch(Throwable $e) {
                return error($e);
            }

            $reflectionParameters = $reflectionFunction->getParameters();

            /** @var PathResolver $pathResolver */
            $pathResolver = PathResolver::findResolver(
                symbolicMethod: $symbolicMethod,
                symbolicPath: $symbolicPath,
                reflectionParameters: $reflectionParameters,
            )->unwrap($error);
            if ($error) {
                return error($error);
            }

            $this->cache[$key] = $pathResolver;


            $requestPathParametersWrapper = $pathResolver->findParametersFromPath($requestPath);

            if ($requestPathParametersWrapper->ok) {
                if ($requestPathParametersWrapper->badRequestEntries) {
                    $badRequestEntries = $requestPathParametersWrapper->badRequestEntries;
                    break;
                }
                $requestPathParameters = $requestPathParametersWrapper->parameters;
                break;
            }
        }

        if (false === $requestPathParameters && !$badRequestEntries) {
            /** @var Unsafe<false|Response> */
            return ok(false);
        }

        $requestQueries = $this->findQueriesFromRequest($request);
        $context        = RequestContext::create(
            key: "$symbolicMethod:$symbolicPath",
            route: $route,
            request: $request,
            requestQueries: $requestQueries,
            requestPathParameters: $requestPathParameters?:[],
            badRequestEntries: $badRequestEntries,
        );

        try {
            $result = $this->httpInvoker->invoke($context)->unwrap($invokeError);
        } catch(Throwable $error) {
            return error($error);
        }

        if ($invokeError) {
            return error($invokeError);
        }

        /** @var Unsafe<false|Response> */
        return ok($result);
    }

    /**
     *
     * @param  Request            $request
     * @return array<string|true>
     */
    private function findQueriesFromRequest(Request $request):array {
        $queries     = [];
        $queryString = $request->getUri()->getQuery();
        $queryChunks = explode('&', preg_replace('/^\?/', '', $queryString, 1));

        foreach ($queryChunks as $chunk) {
            $split = explode('=', $chunk);
            $l     = count($split);
            if (2 === $l) {
                $queries[urldecode($split[0])] = urldecode($split[1] ?? '');
            } elseif (1 === $l && '' !== $split[0]) {
                $queries[urldecode($split[0])] = true;
            }
        }
        return $queries;
    }

    // @phpstan-ignore-next-line
    private function respondWithRedirectToHttps(UriInterface $uri):Response {
        return new Response(HttpStatus::FOUND, [
            "Location" => preg_replace('/^http/', 'https', (string)$uri),
        ]);
    }
}
