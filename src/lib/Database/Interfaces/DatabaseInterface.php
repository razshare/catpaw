<?php
namespace CatPaw\Database\Interface;

use CatPaw\Core\Result;

interface DatabaseInterface {
    /**
     * Query the database.
     * @param  string                              $query
     * @param  array<string,string|int|float|bool> $parameters
     * @return Result<array<array<string,mixed>>>
     */
    public function execute(string $query, array $parameters):Result;
}