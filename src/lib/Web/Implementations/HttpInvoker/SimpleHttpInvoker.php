<?php

namespace CatPaw\Web\Implementations\HttpInvoker;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Amp\Http\Server\Response;
use Amp\Websocket\Server\Websocket;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Container;
use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\DependencySearchResultItem;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;

use const CatPaw\Core\NONE;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Web\Accepts;
use CatPaw\Web\Body;
use function CatPaw\Web\failure;
use CatPaw\Web\HttpStatus;
use CatPaw\Web\Interfaces\HttpInvokerInterface;
use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Interfaces\SessionInterface;
use CatPaw\Web\Page;
use CatPaw\Web\RequestContext;

use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_HTML;


use Psr\Log\LoggerInterface;
use Throwable;

#[Provider]
class SimpleHttpInvoker implements HttpInvokerInterface {
    /**
     * 
     * @param  Result<mixed>            $value
     * @return Result<ResponseModifier>
     */
    private static function renderResult(Result $value):Result {
        static $pico      = '';
        static $style     = '';
        static $allStyles = '';
        $result           = $value->unwrap($error);

        if (NONE === $result || $result instanceof None) {
            $result = '';
        }

        if ($error) {
            $message = $error->getMessage();
            $trace   = $error->getTrace();

            if ('' === $style) {
                $style = File::readFile(asFileName(__DIR__, './error.css'))->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }

            if ('' === $pico) {
                $pico = File::readFile(asFileName(__DIR__, './pico.css'))->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }

            if ('' === $allStyles) {
                $allStyles = "<style>$pico\n$style</style>";
            }


            ["file" => $mainFile, "line" => $mainLine] = $trace[0];

            $content = <<<HTML
                $allStyles
                <div class="error-screen">
                <div class="error-header">
                    <h1>Error</h1>
                    <div class="error-message">$message</div>
                    <a href="vscode://file$mainFile:$mainLine:0" class="error-jump-main">Jump to error</a>
                </div>
                <div class="error-body">
                HTML;

            foreach ($trace as ['file' => $file, 'line' => $line]) {
                if ('' === trim($file)) {
                    continue;
                }
                $content .= <<<HTML
                        <div class="error-trace-entry">
                            <span class="error-trace-line">$line</span>
                            <a href="vscode://file$file:$line:0" class="error-trace-file">$file</a>
                        </div>
                    HTML;
            }

            $content .= <<<HTML
                </div>
                </div>
                HTML;
            $modifier = success($content)->as(TEXT_HTML);
        } else {
            try {
                $modifier = success((string)$result)->as(TEXT_HTML);
            } catch(Throwable $error) {
                $modifier = self::renderResult(error($error))->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
        }
        return ok($modifier);
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

        if ($modifier instanceof Result) {
            $modifier = self::renderResult($modifier)->unwrap($error);
            if ($error) {
                return error($error);
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
                Accepts::class     => static fn () => Accepts::createFromRequest($context->request),
                Page::class        => static function() use ($context) {
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
                    return (string)$context->requestPathParameters[$result->name];
                },
            ],
            defaultArguments: [],
            context: $context,
        );
    }
}
