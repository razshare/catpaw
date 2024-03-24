<?php
namespace CatPaw\Cui\Contracts;

interface CuiContract {
    /**
     * Destroy a reference from memory.
     * @param  mixed $key Reference key.
     * @return void
     */
    function Destroy(mixed $key):void;
    function NewGui():void;
    function StartGui():void;
    function Fprintln(View $view, string $content):void;
    function NewView(string $name, int $x0, int $y0, int $x1, int $y1):View;
    function MaxX():int;
    function MaxY():int;
    function Update(int $increase):int;
}

interface View {
}
