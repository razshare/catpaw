<?php
namespace CatPaw\Go\Contracts;

interface GoContract {
    /**
     * Destroy a reference from memory.
     * @param  mixed $key Reference key.
     * @return void
     */
    function Destroy(mixed $key):void;
}
