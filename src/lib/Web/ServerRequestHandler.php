<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use CatPaw\Web\Interfaces\FileServerInterface;
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

    public function handleRequest(Request $request): Response {
        try {
            $responseFromFileServer = $this->fileServer->serve($request);
            if (HttpStatus::NOT_FOUND === $responseFromFileServer->getStatus()) {
                $responseAttempt = $this->resolver->resolve($request);
                if ($responseAttempt->error) {
                    throw $responseAttempt->error;
                }
                return $responseAttempt->value;
            }
            return $responseFromFileServer;
        } catch (Throwable $e) {
            $message    = $e->getMessage();
            $fileName   = $e->getFile();
            $lineNumber = $e->getLine();
            $this->logger->error("$message", [
                "file" => $fileName,
                "line" => $lineNumber,
            ]);
            return new Response(HttpStatus::INTERNAL_SERVER_ERROR, [], HttpStatus::getReason(HttpStatus::INTERNAL_SERVER_ERROR));
        }
    }
}