<?php
namespace CatPaw\Database\Interfaces;

use CatPaw\Core\Result;

interface DatabaseInterface {
    /**
     * Send a query to the database.
     * @param  string                              $query
     * @param  array<string,string|int|float|bool> $parameters
     * @return Result<array<array<string,mixed>>>
     */
    public function send(string $query, array $parameters):Result;
}