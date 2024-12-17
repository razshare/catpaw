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

use CatPaw\Core\Result;
use CatPaw\Document\Interfaces\DocumentInterface;
use CatPaw\Web\Accepts;
use CatPaw\Web\Body;
use function CatPaw\Web\failure;
use CatPaw\Web\HttpStatus;
use CatPaw\Web\Interfaces\HttpInvokerInterface;
use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Interfaces\SessionInterface;
use CatPaw\Web\Page;
use CatPaw\Web\Query;
use CatPaw\Web\QueryItem;
use CatPaw\Web\RequestContext;

use function CatPaw\Web\success;


use Psr\Log\LoggerInterface;
use Throwable;


#[Provider]
class SimpleHttpInvoker implements HttpInvokerInterface {
    public function __construct(private DocumentInterface $document) {
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
                echo $error.PHP_EOL;
                break;
            }
        }

        $modifier = $function(...$dependencies);

        if ($mountContext = $context->route->mountContext) {
            $modifier = $this->document->render($mountContext->fileName, $modifier);
        }

        if ($modifier instanceof Result) {
            $value = $modifier->unwrap($error);
            if ($error) {
                return error($error);
            }

            if ($value instanceof ResponseModifier) {
                $modifier = $value;
            } else {
                $modifier = success($value);
            }
        } else if ($modifier instanceof Websocket) {
            $websocket = $modifier;
            $modifier  = success($websocket->handleRequest($context->request));
        }

        if (!$modifier instanceof ResponseModifier) {
            try {
                $modifier = success($modifier);
            } catch(Throwable $error) {
                return error($error);
            }
        }

        $modifier->withRequestContext($context);

        foreach ($dependencies as $dependency) {
            if ($dependency instanceof SessionInterface) {
                $dependency->apply($modifier);
            }
        }

        foreach ($onResponses as $onResponse) {
            $onResponse->onResponse($context->request, $modifier);
            if ($error) {
                echo $error.PHP_EOL;
                break;
            }
        }

        return $modifier->response();
    }

    private function createDependenciesOptions(RequestContext $context):DependenciesOptions {
        $key = $context->key;
        return DependenciesOptions::create(
            key: $key,
            overwrites: [
                SessionInterface::class => function() use ($context) {
                    $result = Container::get(SessionInterface::class, $context->request)->unwrap($error);
                    if ($error) {
                        $logger = Container::get(LoggerInterface::class)->unwrap($errorLogger);
                        if ($errorLogger) {
                            echo $errorLogger.PHP_EOL;
                            return false;
                        }
                        $logger->error($error);
                        return false;
                    }
                    if ($result instanceof Result) {
                        $result = $result->unwrap($error);
                        if ($error) {
                            $logger = Container::get(LoggerInterface::class)->unwrap($errorLogger);
                            if ($errorLogger) {
                                echo $errorLogger.PHP_EOL;
                                return false;
                            }
                            $logger->error($error);
                            $logger->error($error);
                            return false;
                        }
                    }
                    return $result;
                },
                Request::class     => static fn () => $context->request,
                RequestBody::class => static fn () => $context->request->getBody(),
                Body::class        => static fn () => new Body($context->request),
                QueryItem::class   => static function(DependencySearchResultItem $result) use ($context) {
                    return new QueryItem($context->requestQueries[$result->name] ?? '');
                },
                Query::class => static function() use ($context) {
                    $map = [];
                    foreach ($context->requestQueries as $key => $value) {
                        $map[$key] = new QueryItem($value);
                    }

                    return new Query($map);
                },
                Accepts::class => static fn () => Accepts::createFromRequest($context->request),
                Page::class    => static function() use ($context) {
                    $start = $context->requestQueries['start'] ?? 0;
                    $size  = $context->requestQueries['size']  ?? 10;
                    return
                        Page::create(start: $start, size: $size)
                            ->withUri($context->request->getUri());
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
                    $text = (string)$context->requestPathParameters[$result->name];
                    return match ($text) {
                        ''      => $result->defaultValue,
                        default => $text,
                    };
                },
            ],
            defaultArguments: [],
            context: $context,
        );
    }
}
