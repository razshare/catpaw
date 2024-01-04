<?php

namespace CatPaw\Web;

use function CatPaw\error;
use function CatPaw\ok;

use CatPaw\Unsafe;
use CatPaw\Web\Attributes\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use React\Http\Message\Response;
use ReflectionFunction;

use Throwable;

class RouteResolver {
    private static $cache = [];
    /**
     * @param  RequestInterface       $request
     * @return Unsafe<false|Response>
     */
    public function resolve(
        RequestInterface $request,
    ):Unsafe {
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
                if (false === $parameters) {
                    break;
                }
                
                $requestPathParameters = $parameters;
                continue;
            }

            try {
                $reflectionFunction = new ReflectionFunction($function);
            } catch(Throwable $e) {
                return error($e);
            }

            $reflectionParameters = $reflectionFunction->getParameters();

            /** @var Unsafe<PathResolver> */
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
            return ok(new Response(status:HttpStatus::NOT_FOUND, reason:HttpStatus::getReason(HttpStatus::NOT_FOUND)));
        }
        
        $requestQueryStrings = $this->findQueryStringsFromRequest($request);

        $response = new Response();

        $context = RequestContext::create(
            key: "$symbolicMethod:$symbolicPath",
            route: $route,
            server: $server,
            request: $request,
            response: $response,
            requestQueryStrings: $requestQueryStrings,
            requestPathParameters: $requestPathParameters,
        );

        $resultAttmpt = $this->invoker->invoke($context);
        if ($resultAttmpt->error) {
            return error($resultAttmpt->error);
        }

        return ok($resultAttmpt->value);
    }

    private function findQueryStringsFromRequest(RequestInterface $request):array {
        $queries     = [];
        $queryChunks = explode('&', preg_replace('/^\?/', '', $request->getUri()->getQuery(), 1));
        $query       = [];

        foreach ($queryChunks as $chunk) {
            $split = explode('=', $chunk);
            $l     = count($split);
            if (2 === $l) {
                $query[urldecode($split[0])] = urldecode($split[1] ?? '');
            } elseif (1 === $l && '' !== $split[0]) {
                $query[urldecode($split[0])] = true;
            }
        }
        return $queries;
    }

    private function respondWithRedirectToHttps(UriInterface $uri):ResponseInterface {
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