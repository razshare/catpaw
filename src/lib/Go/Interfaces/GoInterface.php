<?php
namespace CatPaw\Go\Interfaces;

use CatPaw\Core\Unsafe;

interface GoInterface {
    /**
     * Load a Go library.
     * @template T
     * @param  class-string<T> $interface Contract interface, as in - what functions the go library exposes.
     * @param  string          $fileName  Main go file.
     * @return Unsafe<T>
     */
    public static function load(string $interface, string $fileName):Unsafe;
}