<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Response;
use CatPaw\Core\Unsafe;
use CatPaw\Web\RequestContext;


interface ResponseModifier {
    public function setData(mixed $data):void;
    public function setRequestContext(RequestContext $context):void;
    public function setCookies(ResponseCookie ...$cookies):void;
    public function addCookies(ResponseCookie ...$cookies):void;
    /**
     *
     * @param  array<string,string> $headers
     * @return void
     */
    public function setHeaders(array $headers):void;
    public function setStatus(int $status):void;
    public function getData():mixed;
    /**
     *
     * @return array<string,string>
     */
    public function getHeaders():array;
    public function getStatus():int;
    public function as(string $contentType):self;
    public function item():self;
    /**
     *
     * @return Unsafe<Response>
     */
    public function getResponse():Unsafe;
}
