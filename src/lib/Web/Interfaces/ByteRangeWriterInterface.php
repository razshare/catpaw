<?php

namespace CatPaw\Web\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

interface ByteRangeWriterInterface {
    /**
     * Get the content length of the response.
     * @return Unsafe<int>
     */
    public function contentLength():Unsafe;

    /**
     * Get the range query of the request.
     * @return Unsafe<string>
     */
    public function rangeQuery():Unsafe;

    /**
     * Get the content type of the response.
     * @return Unsafe<string>
     */
    public function contentType():Unsafe;

    /**
     * This method will be invoked before `send`.
     * You can set required streams and variables in here.
     * @return Unsafe<None>
     */
    public function start():Unsafe;

    /**
     * This method provides `callable $emit`, which you can use to send data to the response stream.
     *
     * This method is called multiple times, once for every requested range.
     * @param  int            $start  beginning of the requested range.
     * @param  int            $length length of the requested range.
     * @return Unsafe<string>
     */
    public function send(int $start, int $length):Unsafe;

    /**
     * This will method will be invoked after `send`.
     * @return Unsafe<None>
     */
    public function close():Unsafe;
}
