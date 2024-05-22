<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use CatPaw\Core\DependenciesOptions;

use CatPaw\Core\DependencySearchResultItem;
use function CatPaw\Core\error;

use CatPaw\Core\Unsafe;
use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Interfaces\SessionInterface;
use Psr\Log\LoggerInterface;

class HttpInvoker {
    public static function create():self {
        return new self();
    }
    
    private function __construct() {
    }

    /**
     * @param  RequestContext   $context
     * @return Unsafe<Response>
     */
    public function invoke(RequestContext $context):Unsafe {
        $badRequestEntries = $context->badRequestEntries;
        if ($badRequestEntries) {
            $modifier = failure(join("\n", $badRequestEntries), HttpStatus::BAD_REQUEST);
            return $modifier->getResponse();
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

        if ($modifier instanceof View) {
            $viewFileName        = asFileName($context->route->workDirectory, 'view.twig');
            $fileNameStringified = (string)$viewFileName;
            if ('' !== $fileNameStringified) {
                $view     = $modifier;
                $modifier = twig($fileNameStringified)->withProperties($view->properties)->response(
                    status: $view->status,
                    headers: $view->headers,
                );
            } else {
                $modifier = failure();
            }
        }

        if (!$modifier instanceof ResponseModifier) {
            return error("A route handler must always return a response modifier but route handler {$context->key} did not.");
        }

        $modifier->setRequestContext($context);

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

        return $modifier->getResponse();
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
                    if ($result instanceof Unsafe) {
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
                Server::class  => static fn () => $context->server,
                Request::class => static fn () => $context->request,
                Accepts::class => static fn () => Accepts::createFromRequest($context->request),
                Filter::class  => static fn () => Filter::createFromRequest($context->request),
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
