<?php

namespace CatPaw\Web\Implementations\RequestHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Container;
use CatPaw\Web\HttpStatus;
use CatPaw\Web\Interfaces\FileServerInterface;
use CatPaw\Web\Interfaces\RouteResolverInterface;
use Psr\Log\LoggerInterface;
use Throwable;

#[Provider]
readonly class SimpleRequestHandler implements RequestHandler {
    public function __construct(
        private LoggerInterface $logger,
        private RouteResolverInterface $routeResolver,
    ) {
    }

    private function createResponseFromError(Throwable $error): Response {
        $message    = $error->getMessage();
        $fileName   = $error->getFile();
        $lineNumber = $error->getLine();
        $this->logger->error("$message", [
            "file" => $fileName,
            "line" => $lineNumber,
        ]);
        return new Response(HttpStatus::INTERNAL_SERVER_ERROR, [], HttpStatus::reason(HttpStatus::INTERNAL_SERVER_ERROR));
    }

    public function handleRequest(Request $request): Response {
        try {
            $response = $this->routeResolver->resolve($request)->unwrap($error);
            if ($error) {
                return $this->createResponseFromError($error);
            }
            if (!$response) {
                $fileServer = Container::get(FileServerInterface::class)->unwrap($error);
                if ($error) {
                    return $this->createResponseFromError($error);
                }
                $responseFromFileServer = $fileServer->serve($request);
                return $responseFromFileServer;
            }
            return $response;
        } catch (Throwable $error) {
            return $this->createResponseFromError($error);
        }
    }
}
