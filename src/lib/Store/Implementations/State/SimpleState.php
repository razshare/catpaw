<?php
namespace CatPaw\Store\Implementations\State;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Store\Interfaces\StateInterface;
use CatPaw\Store\Writable;

#[Provider]
class SimpleState implements StateInterface {
    /** @var array<string,Writable<mixed>> */
    private array $map = [];

    public function of(string $name, mixed $defaultValue = false):Writable {
        if (!isset($this->map[$name])) {
            $this->map[$name] = new Writable($defaultValue);
        }
        return $this->map[$name];
    }
}
