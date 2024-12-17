<?php
namespace CatPaw\Web;

class Query {
    /**
     * Get all queries.
     * @param array<string,QueryItem> $map
     */
    public function __construct(public array $map) {
    }

    public function has(string $key):bool {
        return isset($this->map[$key]);
    }

    /**
     * 
     * @param  string    $key
     * @return QueryItem
     */
    public function get(string $key):QueryItem {
        return $this->map[$key];
    }

    /**
     * Get all queries.
     * @return array<string,QueryItem>
     */
    public function all():array {
        return $this->map;
    }
}