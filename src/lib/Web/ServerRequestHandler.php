<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use CatPaw\Web\Interfaces\FileServerInterface;
use Error;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class ServerRequestHandler implements RequestHandler {
    public static function create(
        LoggerInterface $logger,
        FileServerInterface $fileServer,
        RouteResolver $resolver,
    ):self {
        return new self($logger, $fileServer, $resolver);
    }

    private function __construct(
        private LoggerInterface $logger,
        private FileServerInterface $fileServer,
        private RouteResolver $resolver,
    ) {
    }

    private function createResponseFromError(Error $error):Response {
        $message    = $error->getMessage();
        $fileName   = $error->getFile();
        $lineNumber = $error->getLine();
        $this->logger->error("$message", [
            "file" => $fileName,
            "line" => $lineNumber,
        ]);
        return new Response(HttpStatus::INTERNAL_SERVER_ERROR, [], HttpStatus::getReason(HttpStatus::INTERNAL_SERVER_ERROR));
    }

    public function handleRequest(Request $request): Response {
        try {
            $response = $this->resolver->resolve($request)->try($error);
            if ($error) {
                return $this->createResponseFromError($error);
            }
            if (!$response) {
                $responseFromFileServer = $this->fileServer->serve($request);
                return $responseFromFileServer;
            }
            return $response;
        } catch (Throwable $error) {
            return $this->createResponseFromError($error);
        }
    }
}