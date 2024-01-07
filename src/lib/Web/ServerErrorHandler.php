<?php
namespace CatPaw\Web;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Psr\Log\LoggerInterface;

class ServerErrorHandler implements ErrorHandler {
    public static function create(LoggerInterface $logger):self {
        return new self($logger);
    }

    private function __construct(private LoggerInterface $logger) {
    }

    public function handleError(int $status, ?string $reason = null, ?Request $request = null): Response {
        $this->logger->error($reason ?? 'Unknown reason.');
        return new Response(HttpStatus::INTERNAL_SERVER_ERROR);
    }
}