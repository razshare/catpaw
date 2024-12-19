<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Response;
use CatPaw\Core\Result;
use CatPaw\Web\RequestContext;


interface ResponseModifierInterface {
    public function withData(mixed $data):self;
    public function withRequestContext(RequestContext $context):self;
    public function withCookies(ResponseCookie ...$cookies):self;
    public function addCookies(ResponseCookie ...$cookies):self;
    /**
     * @param  array<string,string> $headers
     * @return self
     */
    public function withHeaders(array $headers):self;
    public function withStatus(int $status):self;
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
