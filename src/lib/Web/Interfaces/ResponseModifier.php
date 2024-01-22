<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Core\Unsafe;

interface ResponseModifier {
    public function setData(mixed $data);
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
