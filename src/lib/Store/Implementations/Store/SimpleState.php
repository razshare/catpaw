<?php
namespace CatPaw\Store\Implementations\Store;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Store\Interfaces\StateInterface;
use CatPaw\Store\Writable;
use function CatPaw\Store\writable;

#[Provider]
class SimpleState implements StateInterface {
    /** @var array<string,Writable<mixed>> */
    private array $map = [];

    public function of(string $name, mixed $defaultValue = false):Writable {
        if (!isset($this->map[$name])) {
            $this->map[$name] = writable($defaultValue);
        }
        return $this->map[$name];
    }
}
