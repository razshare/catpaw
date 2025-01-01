<?php
namespace CatPaw\Web;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\WritableIterableStream;
use Amp\Http\Server\Response;
use function CatPaw\Core\duplex;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use Throwable;

class ServerSentEvent {
    private readonly WritableIterableStream $writer;
    private readonly ReadableIterableStream $reader;
    /**
     *
     * @param  callable(callable(string):void):void $emitter
     * @param  array<string,string>                 $headers
     * @return void
     */
    private function __construct(
        private mixed $emitter,
        private array $headers = [],
    ) {
        [$reader, $writer] = duplex();
        $this->reader      = $reader;
        $this->writer      = $writer;
        ($this->emitter)($writer->write(...));
    }

    /**
     * Build the response `Response`.
     * @return Result<Response>
     */
    function toResponse():Result {
        $response = new Response();
        $response->setBody($this->reader);
        try {
            $response->setHeaders($this->headers);
        } catch(Throwable $e) {
            return error($e);
        }
        return ok($response);
    }

    /**
     * @return WritableIterableStream The underlying writable stream used by the event.
     */
    function writer():WritableIterableStream {
        return $this->writer;
    }

    /**
     * @return ReadableIterableStream The underlying readable stream used by the event.
     */
    function reader():ReadableIterableStream {
        return $this->reader;
    }

    /**
     * Set a response header.
     *
     * ### Note
     * The following headers are set by default for all events:
     * ```json
     * {
     *   "Cache-Control": "no-store",
     *   "Content-Type": "text/event-stream",
     *   "Connection": "keep-alive",
     * }
     * ```
     *
     * ### Warning
     * Overwriting the default headers is allowed.
     *
     * Overwriting header `Content-Type` to something other than `text/event-stream` will break the SSE contract and the event will stop working as intended.
     * @param  string          $name
     * @param  string          $value
     * @return ServerSentEvent
     */
    function withHeader(string $name, string $value):self {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Unset a response header.
     *
     * ### Note
     * The following headers are set by default for all events:
     * ```json
     * {
     *   "Cache-Control": "no-store",
     *   "Content-Type": "text/event-stream",
     *   "Connection": "keep-alive",
     * }
     * ```
     *
     * ### Warning
     * Unsetting the default headers is allowed.
     *
     * Unsetting header `Content-Type` will break the SSE contract and the event will stop working as intended.
     * @param  string          $name
     * @return ServerSentEvent
     */
    function withoutHeader(string $name):self {
        unset($this->headers[$name]);
        return $this;
    }
}
