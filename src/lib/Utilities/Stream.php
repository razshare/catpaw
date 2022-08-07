<?php

namespace CatPaw\Utilities;

use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;


class Stream {
    private ?ResourceOutputStream $writer = null;
    private ?ResourceInputStream $reader  = null;
    private function __construct(private mixed $stream) {
    }

    public static function of(mixed $resource) {
        return new self($resource);
    }

    public function write(string $chunk) {
        if (!$this->writer) {
            $this->writer = new ResourceOutputStream($this->stream);
        }
        return $this->writer->write($chunk);
    }

    public function read() {
        if (!$this->reader) {
            $this->reader = new ResourceInputStream($this->stream);
        }
        return yield $this->reader->read();
    }

    public function close() {
        if ($this->writer) {
            $this->writer->close();
        }
        if ($this->reader) {
            $this->reader->close();
        }
    }
}