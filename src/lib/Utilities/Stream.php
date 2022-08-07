<?php

namespace CatPaw\Utilities;

use Amp\ByteStream\ResourceInputStream;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Promise;

class Stream {
    private ?ResourceOutputStream $writer = null;
    private ?ResourceInputStream $reader  = null;
    private function __construct(private mixed $stream) {
    }

    public static function of(mixed $resource):self {
        return new self($resource);
    }

    public function write(string $chunk):Promise {
        if (!$this->writer) {
            $this->writer = new ResourceOutputStream($this->stream);
        }
        return $this->writer->write($chunk);
    }

    public function read():Promise {
        if (!$this->reader) {
            $this->reader = new ResourceInputStream($this->stream);
        }
        return $this->reader->read();
    }

    public function close():void {
        if ($this->writer) {
            $this->writer->close();
        }
        if ($this->reader) {
            $this->reader->close();
        }
    }
}