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

    public function get(string $key):mixed {
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