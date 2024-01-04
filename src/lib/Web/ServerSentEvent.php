<?php
namespace CatPaw\Web;

use function Amp\async;
use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\WritableIterableStream;
use Amp\Http\Server\Response;

use function CatPaw\duplex;
use Closure;

class ServerSentEvent {
    private function __construct(
        private ReadableIterableStream $reader,
        private WritableIterableStream $writer,
        private array $headers = []
    ) {
    }

    /**
     * Create a new server sent event.
     * @param  Closure(callable(string):void):void $emitter
     * @return ServerSentEvent
     */
    static function create(Closure $emitter):self {
        [$reader, $writer] = duplex();
        async($emitter, $writer->write(...));
        return new self(
            reader: $reader,
            writer: $writer,
        );
    }

    /**
     * Build the response `Response`.
     * @return Response
     */
    function toResponse():Response {
        $response = new Response();
        $response->setBody($this->reader);
        $response->setHeaders($this->headers);
        return $response;
    }

    /**
     * @return WritableIterableStream The underlying writable stream used by the event.
     */
    function getWriter():WritableIterableStream {
        return $this->writer;
    }

    /**
     * @return ReadableIterableStream The underlying readable stream used by the event.
     */
    function getReader():ReadableIterableStream {
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
     * @param string $name
     * @param string $value
     */
    function setHeader(string $name, string $value):self {
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
    function unsetHeader(string $name):self {
        unset($this->headers[$name]);
        return $this;
    }
}