<?php

namespace CatPaw\Web\Interfaces;

use CatPaw\Core\Unsafe;

interface ByteRangeWriterInterface {
    /**
     * Get the content length of the response.
     * @return Unafe<int>
     */
    public function getContentLength():Unsafe;

    /**
     * Get the range query of the request.
     * @return Unafe<string>
     */
    public function getRangeQuery():Unsafe;
    
    /**
     * Get the content type of the response.
     * @return Unafe<string>
     */
    public function getContentType():Unsafe;

    /**
     * This method will be invoked before `send`.
     * You can set required streams and variables in here.
     * @return Unafe<void>
     */
    public function start():Unsafe;

    /**
     * This method provides `callable $emit`, which you can use to send data to the response stream.
     * 
     * This method is called multiple times, once for every requested range.
     * @param  int           $start  beginning of the requested range.
     * @param  int           $length length of the requested range.
     * @return Unafe<string>
     */
    public function send(int $start, int $length):Unsafe;
    
    /**
     * This will method will be invoked after `send`.
     * @return Unafe<void>
     */
    public function close():Unsafe;
}
