<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Response;
use CatPaw\Core\Unsafe;
use CatPaw\Web\RequestContext;

interface ResponseModifier {
    public function setData(mixed $data);
    public function setRequestContext(RequestContext $context);
    public function setCookies(ResponseCookie ...$cookies);
    public function addCookies(ResponseCookie ...$cookies);
    public function setHeaders(array $headers);
    public function setStatus(int $status);
    public function getData():mixed;
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
