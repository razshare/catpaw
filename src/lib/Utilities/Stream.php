<?php

namespace CatPaw\Utilities;

use Amp\ByteStream\{ClosedException, PendingReadError, ResourceInputStream, ResourceOutputStream};
use function Amp\call;

use Amp\Loop\InvalidWatcherError;
use Amp\Promise;

use Error;

class Stream {
    private ?ResourceOutputStream $writer = null;
    private ?ResourceInputStream $reader  = null;
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
     * @return Promise<void>
     */
    public function write(string $chunk):Promise {
        if (!$this->writer) {
            $this->writer = new ResourceOutputStream($this->resource, $this->chunkSize);
        }
        return $this->writer->write($chunk);
    }

    /**
     * Read data from the stream.
     * @throws PendingReadError
     * @throws InvalidWatcherError
     * @return Promise<null|string>
     */
    public function read():Promise {
        if (!$this->reader) {
            $this->reader = new ResourceInputStream($this->resource, $this->chunkSize ?? ResourceInputStream::DEFAULT_CHUNK_SIZE);
        }
        return $this->reader->read();
    }

    /**
     * Close the stream.
     * @throws Error
     * @return Promise<void>
     */
    public function close():Promise {
        return call(function() {
            if ($this->writer) {
                yield $this->writer->end();
            }
            if ($this->reader) {
                $this->reader->close();
            }
        });
    }
}