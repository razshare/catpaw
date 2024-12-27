<?php
namespace CatPaw\Database\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;
use CatPaw\Web\Page;

interface SqlBuilderInterface {
    /**
     * @template T
     * @param  class-string<T> $className
     * @return Result<false|T>
     */
    public function one(string $className):Result;

    /**
     * @template T
     * @param  class-string<T>  $className
     * @return Result<array<T>>
     */
    public function many(string $className):Result;

    /**
     * @return Result<None>
     */
    public function none():Result;

    public function select(string ...$domain):self;

    public function from(string $table):self;

    public function insert():self;

    public function into(string $into, string ...$domain):self;

    public function limit(int $offset, int $count = 10):self;
    
    public function page(Page $page):self;

    public function value():self;

    public function values():self;

    public function update(string $table):self;

    /**
     * 
     * @param  array<string,mixed>|object $items
     * @return SqlBuilderInterface
     */
    public function set(array|object $items):self;

    public function not(bool $literal = true):self;

    public function equals():self;

    public function notEquals():self;

    public function greaterThan():self;
    
    public function lesserThan():self;
    
    public function greaterThanOrEquals():self;
    
    public function lesserThanOrEquals():self;

    public function like():self;

    public function name(string $name):self;

    public function parameter(string $name, mixed $value):self;

    public function where():self;

    public function between():self;

    public function and():self;

    public function or():self;

    public function in():self;

    public function having():self;

    public function group():self;

    public function by():self;
}