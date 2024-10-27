<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Response;
use CatPaw\Core\Result;
use CatPaw\Web\RequestContext;


interface ResponseModifier {
    public function withData(mixed $data):void;
    public function withRequestContext(RequestContext $context):void;
    public function withCookies(ResponseCookie ...$cookies):void;
    public function addCookies(ResponseCookie ...$cookies):void;
    /**
     *
     * @param  array<string,string> $headers
     * @return void
     */
    public function withHeaders(array $headers):void;
    public function withStatus(int $status):void;
    public function data():mixed;
    /**
     *
     * @return array<string,string>
     */
    public function headers():array;
    public function status():int;
    public function as(string $contentType):self;
    public function item():self;
    /**
     *
     * @return Result<Response>
     */
    public function response():Result;
}
