<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Core\Unsafe;

interface ResponseModifier {
    public function as(string $contentType):self;
    public function item():self;
    /**
     *
     * @return Unsafe<Response>
     */
    public function getResponse():Unsafe;
}
