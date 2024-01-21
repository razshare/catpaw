<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use CatPaw\Core\Container;
use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\DependencySearchResultItem;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;

use CatPaw\Core\Unsafe;
use CatPaw\Web\Interfaces\ResponseModifier;

class HttpInvoker {
    public static function create(
        Server $server,
        false|SessionOperationsInterface $sessionOperations,
    ):self {
        return new self(
            $server,
            $server->router,
            $sessionOperations,
        );
    }

    /**
     * @param Server                           $server
     * @param Router                           $router
     * @param false|SessionOperationsInterface $sessionOperations
     * @param false|Response                   $badRequestNoContentType
     * @param false|Response                   $badRequestCantConsume
     */
    private function __construct(
        private readonly Server $server,
        private readonly Router $router,
        private readonly false|SessionOperationsInterface $sessionOperations,
        private false|Response $badRequestNoContentType = false,
        private false|Response $badRequestCantConsume = false,
    ) {
        if (!$this->badRequestNoContentType) {
            $this->badRequestNoContentType = new Response(HttpStatus::BAD_REQUEST, [], '');
        }
        if (!$this->badRequestCantConsume) {
            $this->badRequestCantConsume = new Response(HttpStatus::BAD_REQUEST, [], '');
        }
    }

    /**
     * @param  RequestContext   $context
     * @return Unsafe<Response>
     */
    public function invoke(RequestContext $context):Unsafe {
        $badRequestEntries = $context->badRequestEntries;
        if ($badRequestEntries) {
            return ok(failure(join("\n", $badRequestEntries), HttpStatus::BAD_REQUEST));
        }
        $onRequests         = $context->route->onRequest;
        $onResults          = $context->route->onResult;
        $reflectionFunction = $context->route->reflectionFunction;
        $function           = $context->route->function;

        $options = $this->createDependenciesOptions($context);

        $dependencies = Container::dependencies($reflectionFunction, $options)->try($error);

        if ($error) {
            return error($error);
        }

        foreach ($onRequests as $onRequest) {
            $onRequest->onRequest($context->request);
        }

        $modifier = $function(...$dependencies);

        if (!$modifier instanceof ResponseModifier) {
            return error("A route handler must always return a response modifier but route handler {$context->key} did not.");
        }

        foreach ($onResults as $onResult) {
            $onResult->onResult($context->request, $modifier);
        }

        if ($sessionIdCookie = Cookie::findFromRequestContextByName($context, 'session-id')) {
            $this->sessionOperations->persistSession($sessionIdCookie->value);
        }

        return $modifier->getResponse();
    }

    private function createDependenciesOptions(RequestContext $context):DependenciesOptions {
        $key = $context->key;
        return DependenciesOptions::create(
            key: $key,
            overwrites: [
                Server::class  => static fn () => $context->server,
                Request::class => static fn () => $context->request,
                Accepts::class => static fn () => Accepts::createFromRequest($context->request),
                Page::class    => static function() use ($context) {
                    $start = $context->requestQueries['start'] ?? 0;
                    $size  = $context->requestQueries['size']  ?? 10;
                    return
                        Page::create(start: $start, size: $size)
                            ->setUri($context->request->getUri());
                },
            ],
            provides: [
                'bool' => static function(DependencySearchResultItem $result) use ($context) {
                    if (!isset($context->requestPathParameters[$result->name])) {
                        return null;
                    }
                    return (bool)$context->requestPathParameters[$result->name];
                },
                'float' => static function(DependencySearchResultItem $result) use ($context) {
                    if (!isset($context->requestPathParameters[$result->name])) {
                        return null;
                    }
                    return (float)$context->requestPathParameters[$result->name];
                },
                'int' => static function(DependencySearchResultItem $result) use ($context) {
                    if (!isset($context->requestPathParameters[$result->name])) {
                        return null;
                    }
                    return (int)$context->requestPathParameters[$result->name];
                },
                'string' => static function(DependencySearchResultItem $result) use ($context) {
                    if (!isset($context->requestPathParameters[$result->name])) {
                        return null;
                    }
                    return (string)$context->requestPathParameters[$result->name];
                },
            ],
            fallbacks: [
                'bool' => static function(DependencySearchResultItem $result) use ($context) {
                    if (!isset($context->requestPathParameters[$result->name])) {
                        return null;
                    }
                    return (bool)$context->requestPathParameters[$result->name];
                },
                'float' => static function(DependencySearchResultItem $result) use ($context) {
                    if (!isset($context->requestPathParameters[$result->name])) {
                        return null;
                    }
                    return (float)$context->requestPathParameters[$result->name];
                },
                'int' => static function(DependencySearchResultItem $result) use ($context) {
                    if (!isset($context->requestPathParameters[$result->name])) {
                        return null;
                    }
                    return (int)$context->requestPathParameters[$result->name];
                },
                'string' => static function(DependencySearchResultItem $result) use ($context) {
                    if (!isset($context->requestPathParameters[$result->name])) {
                        return null;
                    }
                    return (string)$context->requestPathParameters[$result->name];
                },
            ],
            defaultArguments: [],
            context: $context,
        );
    }
}
