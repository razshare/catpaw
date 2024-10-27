<?php

namespace CatPaw\Web\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;

interface ByteRangeWriterInterface {
    /**
     * Get the content length of the response.
     * @return Result<int>
     */
    public function contentLength():Result;

    /**
     * Get the range query of the request.
     * @return Result<string>
     */
    public function rangeQuery():Result;

    /**
     * Get the content type of the response.
     * @return Result<string>
     */
    public function contentType():Result;

    /**
     * This method will be invoked before `send`.
     * You can set required streams and variables in here.
     * @return Result<None>
     */
    public function start():Result;

    /**
     * This method provides `callable $emit`, which you can use to send data to the response stream.
     *
     * This method is called multiple times, once for every requested range.
     * @param  int            $start  beginning of the requested range.
     * @param  int            $length length of the requested range.
     * @return Result<string>
     */
    public function send(int $start, int $length):Result;

    /**
     * This will method will be invoked after `send`.
     * @return Result<None>
     */
    public function close():Result;
}
