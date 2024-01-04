<?php

namespace CatPaw\Web;

use Psr\Http\Message\StreamInterface;
use React\Http\Io\ReadableBodyStream;
use React\Stream\ThroughStream;

class Stream {
    public static function fromString(string $content):StreamInterface {
        $through = new ThroughStream();
        $through->write($content);
        return new ReadableBodyStream($through);
    }

    private function __construct() {
    }
}