<?php

namespace CatPaw\Core\Interfaces;

interface StorageInterface {
    /**
     * Get the storage by reference.
     * @return array<mixed>
     */
    public function &storage():array;

    /**
     * Set the storage by reference.
     * @param  array<mixed> $value
     * @return void
     */
    public function withStorage(array &$value):void;

    /**
     * Get the value.
     * @param  string $key
     * @return mixed
     */
    public function get(string $key):mixed;

    /**
     * Set a value.
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function set(string $key, mixed $value):void;

    /**
     * Unset a value by key.
     * @param  string $key
     * @return void
     */
    public function unset(string $key):void;

    /**
     * Check if a storage contains a key.
     * @param  string $key
     * @return bool
     */
    public function has(string $key):bool;
}
