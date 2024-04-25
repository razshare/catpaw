<?php
namespace CatPaw\Store\Services;

use CatPaw\Core\Attributes\Service;
use CatPaw\Store\Writable;

use function CatPaw\Store\writable;

#[Service]
class StoreService {
    /** @var array<Writable<mixed>> */
    private array $map = [];

    /**
     * Find or create a state if none was found by the given name.
     * @param  string          $name         name of the store; use this to retrieve your store across your application.
     * @param  mixed           $defaultValue a default value for the store.
     * @return Writable<mixed>
     */
    public function of(string $name, mixed $defaultValue = false):Writable {
        if (!($this->map[$name] ?? false)) {
            $this->map[$name] = writable($defaultValue);
        }
        return $this->map[$name];
    }
}
