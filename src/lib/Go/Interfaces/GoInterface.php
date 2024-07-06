<?php
namespace CatPaw\Go\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

interface GoInterface {
    /**
     * 
     * @param  string       $fileName Go file to compile.
     * @return Unsafe<None>
     */
    public function compile(string $fileName):Unsafe;
    
    /**
     * Load a Go library.
     * @template T
     * @param  class-string<T> $interface Contract interface, as in - what functions the go library exposes.
     * @param  string          $fileName  Main go file.
     * @return Unsafe<T>
     */
    public function load(string $interface, string $fileName):Unsafe;
}