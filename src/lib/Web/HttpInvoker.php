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


use function explode;

use function in_array;

use Psr\Http\Message\ResponseInterface;

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
     * @param  RequestContext            $context
     * @return Unsafe<ResponseInterface>
     */
    public function invoke(RequestContext $context):Unsafe {
        $onRequests         = $context->route->onRequest;
        $onResults          = $context->route->onResult;
        $reflectionFunction = $context->route->reflectionFunction;
        $function           = $context->route->function;

        $options = $this->createDependenciesOptionsFromRequestContextAndResponse($context);

        $dependencies = Container::dependencies($reflectionFunction, $options);

        if ($dependencies->error) {
            return error($dependencies->error);
        }

        foreach ($onRequests as $onRequest) {
            $onRequest->onRequest($context->request);
        }

        $result = $function(...$dependencies->value);

        foreach ($onResults as $onResult) {
            $onResult->onResult($context->request, $result);
        }
        
        if ($sessionIdCookie = Cookie::findFromRequestContextByName($context, 'session-id')) {
            $this->sessionOperations->persistSession($sessionIdCookie->value);
        }

        return $this->contextualize($context, $result);
    }

    /**
     * @return Unsafe<ResponseInterface>
     */
    private function contextualize(RequestContext $context, mixed $modifier): Unsafe {
        $consumes = $context->route->consumes;
        $produces = $context->route->produces;

        if (null !== $modifier) {
            $isAlreadyModifier = ($modifier instanceof ResponseModifier);
        
            if (!$isAlreadyModifier) {
                $status = $context->response->getStatus();
                if ($status >= 300) {
                    $modifier = failure((string)$modifier, $status);
                } else {
                    $modifier = success($modifier, $status);
                }
            }

            /** @var ResponseModifier $modifier */

            $modifierIsPrimitive = $modifier->isPrimitive();

            if ($produces) {
                $producesContentType = $produces->hasContentType();
                $producesItem        = $produces->isItem();
                $producesPage        = $produces->isPage();
            } else {
                $producesContentType = false;
                $producesItem        = false;
                $producesPage        = false;
            }

            if ($producesItem) {
                $modifier->withStructure();
            } else if ($producesPage) {
                $modifier->withStructure();
                if (!$modifier->isPage()) {
                    $modifier->page(Page::of(100));
                }
            }
        } else {
            $producesContentType = false;
            $producesItem        = false;
            $producesPage        = false;
            $modifierIsPrimitive = false;
        }
        
        $response = $context->response;

        if (!$response->hasHeader("Content-Type")) {
            if ($produces) {
                if ($producesContentType) {
                    $produced = $produces->getContentType();
                } else if ($modifierIsPrimitive) {
                    $produced = ['text/plain'];
                } else {
                    $produced = ['application/json'];
                }
                $response->setHeader("Content-Type", $produced);
            } else {
                if ($modifierIsPrimitive) {
                    $produced = ['text/plain'];
                } else {
                    $produced = ['application/json'];
                }
                $response->setHeader("Content-Type", $produced);
            }
        } else {
            $produced = $response->getHeader("Content-Type");
        }

        $acceptables = explode(",", $context->request->getHeader("Accept") ?? "*/*");

        foreach ($acceptables as $acceptable) {
            $acceptable = trim($acceptable);
            if (str_starts_with($acceptable, "*/*")) {
                $response->setHeader("Content-Type", $produced[0] ?? 'text/plain');
                return match ($produced[0]) {
                    'application/json' => $modifier->forJson($response),
                    'application/xml'  => ok($modifier->forXml($response)),
                    default            => ok($modifier->forText($response)),
                };
            }
            if (in_array($acceptable, $produced)) {
                $response->setHeader("Content-Type", $acceptable);
                return match ($acceptable) {
                    'application/json' => $modifier->forJson($response),
                    'application/xml'  => ok($modifier->forxml($response)),
                    default            => ok($modifier->forText($response)),
                };
            }
        }

        return ok($response);
    }

    private function createDependenciesOptionsFromRequestContextAndResponse(RequestContext $context):DependenciesOptions {
        $response = $context->response;
        $key      = $context->key;
        return DependenciesOptions::create(
            key: $key,
            overwrites: [
                Server::class   => static fn () => $context->server,
                Request::class  => static fn () => $context->request,
                Response::class => static fn () => $response,
                Page::class     => static function() use ($context) {
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
