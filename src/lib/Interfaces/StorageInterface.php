<?php

namespace CatPaw\Interfaces;

interface StorageInterface {
    /**
     * Get the initial value of the storage.\
     * Useful to initialize or reset the whole storage using `::setStorage`.
     * @return mixed
     */
    public function getStorageInitialValue():mixed;

    /**
     * Get the storage by reference.
     * @param  string $className
     * @return mixed
     */
    public function &getStorage():mixed;

    /**
     * Set the storage by reference.
     * @param  mixed $value
     * @return void
     */
    public function setStorage(mixed &$value):void;

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
     * @param  mixed  $value
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