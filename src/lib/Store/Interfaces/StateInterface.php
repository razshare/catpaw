<?php
namespace CatPaw\Store\Interfaces;

use CatPaw\Store\Writable;

interface StateInterface {
    /**
     * Create a state with a name and a default value.\
     * If one already exists, return it.
     * @param  string          $name         The name of the state.
     * @param  mixed           $defaultValue The default value for the state.
     * @return Writable<mixed> The state.
     */
    public function of(string $name, mixed $defaultValue = false):Writable;
}