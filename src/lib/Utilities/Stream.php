<?php

namespace CatPaw\Utilities;

use Amp\ByteStream\{ClosedException, PendingReadError, ReadableResourceStream, ResourceInputStream, ResourceOutputStream, WritableResourceStream};


use Error;

class Stream {
    private ?WritableResourceStream $writer = null;
    private ?ReadableResourceStream $reader = null;
    private function __construct(
        private mixed $resource,
        private ?int $chunkSize = null,
    ) {
    }

    /**
     * Create a new stream from a resource.
     * @param  mixed    $resource
     * @param  null|int $chunkSize
     * @return Stream
     */
    public static function of(
        mixed $resource,
        ?int $chunkSize = null
    ):self {
        return new self($resource, $chunkSize);
    }

    /**
     * Write data to the stream.
     * @param  string          $chunk
     * @throws ClosedException
     * @return void
     */
    public function write(string $chunk):void {
        if (!$this->writer) {
            $this->writer = new WritableResourceStream($this->resource, $this->chunkSize);
        }
        return $this->writer->write($chunk);
    }

    /**
     * Read data from the stream.
     * @throws PendingReadError
     * @return null|string
     */
    public function read():null|string {
        if (!$this->reader) {
            $this->reader = new ReadableResourceStream($this->resource, $this->chunkSize ?? ReadableResourceStream::DEFAULT_CHUNK_SIZE);
        }
        return $this->reader->read();
    }

    /**
     * Close the stream.
     * @throws Error
     * @return void
     */
    public function close():void {
        if ($this->writer) {
            $this->writer->end();
        }
        if ($this->reader) {
            $this->reader->close();
        }
    }
}