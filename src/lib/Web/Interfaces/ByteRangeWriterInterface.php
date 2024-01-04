<?php

namespace CatPaw\Web\Interfaces;

interface ByteRangeWriterInterface {
    /**
     * Get the content length of the response.
     * @return int
     */
    public function getContentLength():int;

    /**
     * Get the range query of the request.
     * @return string
     */
    public function getRangeQuery():string;
    
    /**
     * Get the content type of the response.
     * @return string
     */
    public function getContentType():string;

    /**
     * This method will be invoked before `send`.
     * You can set required streams and variables in here.
     * @return void
     */
    public function start():void;

    /**
     * This method provides `callable $emit`, which you can use to send data to the response stream.
     * 
     * This method is called multiple times, once for every requested range.
     * @param  callable $emit   send data to the stream.
     * @param  int      $start  beginning of the requested range.
     * @param  int      $length length of the requested range.
     * @return void
     */
    public function send(callable $emit, int $start, int $length):void;
    
    /**
     * This will method will be invoked after `send`.
     * @return void
     */
    public function end():void;
}
