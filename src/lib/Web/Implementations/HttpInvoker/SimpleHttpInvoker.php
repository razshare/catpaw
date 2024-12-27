<?php

namespace CatPaw\Web\Implementations\HttpInvoker;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Response;
use Amp\Websocket\Server\Websocket;
use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Container;
use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\DependencySearchResultItem;
use function CatPaw\Core\error;

use function CatPaw\Core\ok;

use CatPaw\Core\Result;
use CatPaw\Web\Accepts;
use CatPaw\Web\Body;
use function CatPaw\Web\failure;
use CatPaw\Web\HttpStatus;
use CatPaw\Web\Interfaces\HttpInvokerInterface;
use CatPaw\Web\Interfaces\ResponseModifierInterface;
use CatPaw\Web\Interfaces\SessionInterface;
use CatPaw\Web\Page;
use CatPaw\Web\RequestContext;

use function CatPaw\Web\success;


use Throwable;


#[Provider]
class SimpleHttpInvoker implements HttpInvokerInterface {
    public function __construct() {
    }
    /**
     * @param  RequestContext   $context
     * @return Result<Response>
     */
    public function invoke(RequestContext $context):Result {
        $badRequestEntries = $context->badRequestEntries;
        if ($badRequestEntries) {
            $modifier = failure(join("\n", $badRequestEntries), HttpStatus::BAD_REQUEST);
            return $modifier->response();
        }

        $onRequests         = $context->route->onRequest;
        $onResponses        = $context->route->onResponse;
        $reflectionFunction = $context->route->reflectionFunction;
        $function           = $context->route->function;

        $options = $this->createDependenciesOptions($context);

        $dependencies = Container::dependencies($reflectionFunction, $options)->unwrap($error);

        if ($error) {
            return error($error);
        }

        foreach ($onRequests as $onRequest) {
            $onRequest->onRequest($context->request)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        $modifier = $function(...$dependencies);

        foreach ($onResponses as $onResponse) {
            $onResponse->onResponse($context->request, $modifier)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        if ($modifier instanceof Result) {
            $value = $modifier->unwrap($error);
            if ($error) {
                return error($error);
            }
            
            if ($value instanceof ResponseModifierInterface) {
                $modifier = $value;
            } else {
                $modifier = success($value);
            }
        } else if ($modifier instanceof Websocket) {
            $websocket = $modifier;
            $modifier  = success($websocket->handleRequest($context->request));
        } else if (!$modifier instanceof ResponseModifierInterface) {
            try {
                $modifier = success($modifier);
            } catch(Throwable $error) {
                return error($error);
            }
        }

        $modifier->withRequestContext($context);

        foreach ($dependencies as $dependency) {
            if ($dependency instanceof SessionInterface) {
                $dependency->flush($modifier)->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
        }

        return $modifier->response();
    }

    private function createDependenciesOptions(RequestContext $context):DependenciesOptions {
        $key = $context->key;
        return new DependenciesOptions(
            key: $key,
            overwrites: [
                SessionInterface::class => function() use ($context) {
                    $result = Container::get(SessionInterface::class, $context->request)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    if ($result instanceof Result) {
                        $result = $result->unwrap($error);
                        if ($error) {
                            return error($error);
                        }
                    }
                    return ok($result);
                },
                Request::class     => static fn () => $context->request,
                RequestBody::class => static fn () => $context->request->getBody(),
                Body::class        => static fn () => new Body($context->request),
                Accepts::class     => static fn () => Accepts::createFromRequest($context->request),
                Page::class        => static function() use ($context) {
                    $start = $context->requestQueries['start'] ?? 0;
                    $size  = $context->requestQueries['size']  ?? 10;
                    return ok(
                        Page::create(start: $start, size: $size)
                            ->withUri($context->request->getUri())
                    );
                },
            ],
            provides: [
                'bool' => static function(DependencySearchResultItem $item) use ($context) {
                    if (!isset($context->requestPathParameters[$item->name])) {
                        return ok(null);
                    }
                    return ok((bool)$context->requestPathParameters[$item->name]);
                },
                'float' => static function(DependencySearchResultItem $item) use ($context) {
                    if (!isset($context->requestPathParameters[$item->name])) {
                        return ok(null);
                    }
                    return ok((float)$context->requestPathParameters[$item->name]);
                },
                'int' => static function(DependencySearchResultItem $item) use ($context) {
                    if (!isset($context->requestPathParameters[$item->name])) {
                        return ok(null);
                    }
                    return ok((int)$context->requestPathParameters[$item->name]);
                },
                'string' => static function(DependencySearchResultItem $item) use ($context) {
                    if (!isset($context->requestPathParameters[$item->name])) {
                        return ok(null);
                    }
                    return ok((string)$context->requestPathParameters[$item->name]);
                },
            ],
            fallbacks: [
                'bool' => static function(DependencySearchResultItem $item) use ($context) {
                    if (!isset($context->requestPathParameters[$item->name])) {
                        if (!isset($context->requestQueries[$item->name])) {
                            return ok($item->defaultValue);
                        }
                        return ok((bool)$context->requestQueries[$item->name]);
                    }
                    return ok((bool)$context->requestPathParameters[$item->name]);
                },
                'float' => static function(DependencySearchResultItem $item) use ($context) {
                    if (!isset($context->requestPathParameters[$item->name])) {
                        if (!isset($context->requestQueries[$item->name])) {
                            return ok($item->defaultValue);
                        }
                        return ok((float)$context->requestQueries[$item->name]);
                    }
                    return ok((float)$context->requestPathParameters[$item->name]);
                },
                'int' => static function(DependencySearchResultItem $item) use ($context) {
                    if (!isset($context->requestPathParameters[$item->name])) {
                        if (!isset($context->requestQueries[$item->name])) {
                            return ok($item->defaultValue);
                        }
                        return ok((int)$context->requestQueries[$item->name]);
                    }
                    return ok((int)$context->requestPathParameters[$item->name]);
                },
                'string' => static function(DependencySearchResultItem $item) use ($context) {
                    if (!isset($context->requestPathParameters[$item->name])) {
                        if (!isset($context->requestQueries[$item->name])) {
                            return ok($item->defaultValue);
                        }
                        return ok((string)$context->requestQueries[$item->name]);
                    }

                    $value = (string)$context->requestPathParameters[$item->name];
                    return ok(match ($value) {
                        ''      => $item->defaultValue ?? '',
                        default => $value,
                    });
                },
            ],
            defaultArguments: [],
            context: $context,
        );
    }
}
