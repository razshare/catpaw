<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Attributes\Session;
use Psr\Http\Message\UriInterface;
use ReflectionFunction;

use Throwable;

class RouteResolver {
    private static array $cache = [];

    /**
     * @param  Request                $request
     * @return Unsafe<false|Response>
     */
    public function resolve(Request $request):Unsafe {
        $server = $this->server;
        $router = $server->router;

        $requestMethod  = $request->getMethod();
        $symbolicMethod = $requestMethod;

        $routes = $router->findRoutesByMethod($requestMethod);


        $requestPathParameters = false;

        foreach ($routes as $symbolicPath => $route) {
            $key         = "$requestMethod:$symbolicPath";
            $function    = $route->callback;
            $requestPath = urldecode($request->getUri()->getPath());

            if (isset(self::$cache[$key])) {
                /** @var PathResolver $pathResolver */
                $pathResolver = self::$cache[$key];
                $parameters   = $pathResolver->findParametersFromPath($requestPath);
                if (false !== $parameters) {
                    $requestPathParameters = $parameters;
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

            /** @var Unsafe<PathResolver> $pathResolverAttempt */
            $pathResolverAttempt = PathResolver::findResolver(
                symbolicMethod: $symbolicMethod,
                symbolicPath: $symbolicPath,
                parameters: $reflectionParameters,
            );
            if ($pathResolverAttempt->error) {
                return error($pathResolverAttempt->error);
            }
            $pathResolver      = $pathResolverAttempt->value;
            self::$cache[$key] = $pathResolver;


            $requestPathParameters = $pathResolver->findParametersFromPath($requestPath);

            if (false !== $requestPathParameters) {
                break;
            }
        }

        if (false === $requestPathParameters) {
            $response = new Response();
            $response->setStatus(HttpStatus::NOT_FOUND, HttpStatus::getReason(HttpStatus::NOT_FOUND));
            return ok($response);
        }

        $requestQueries = $this->findQueriesFromRequest($request);

        $response = new Response();

        $context = RequestContext::create(
            key: "$symbolicMethod:$symbolicPath",
            route: $route,
            server: $server,
            request: $request,
            response: $response,
            requestQueries: $requestQueries,
            requestPathParameters: $requestPathParameters,
        );

        try {
            $resultAttempt = $this->invoker->invoke($context);
        } catch(Throwable $e) {
            return error($e);
        }
        if ($resultAttempt->error) {
            return error($resultAttempt->error);
        }

        return ok($resultAttempt->value);
    }

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

    private function respondWithRedirectToHttps(UriInterface $uri):Response {
        return new Response(HttpStatus::FOUND, [
            "Location" => preg_replace('/^http/', 'https', (string)$uri),
        ]);
    }

    public static function create(Server $server):self {
        return new self($server);
    }

    public HttpInvoker $invoker;
    private function __construct(private Server $server) {
        $this->invoker = HttpInvoker::create($server, Session::getOperations());
    }
}