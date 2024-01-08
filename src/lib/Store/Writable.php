<?php

namespace CatPaw\Store;

use Closure;

use SplDoublyLinkedList;

class Writable {
    /**
     * @param  mixed $value The initial value of the store
     * @return self
     */
    public static function create(mixed $value):self {
        return new self($value);
    }

    /** @var SplDoublyLinkedList<Closure> */
    protected SplDoublyLinkedList $callbacks;

    private function __construct(
        protected mixed $value
    ) {
        $this->callbacks = new SplDoublyLinkedList();
        $this->callbacks->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP);
    }

    /**
     * Get the value of the store.
     * @return mixed
     */
    public function get(): mixed {
        return $this->value;
    }

    /**
     * Set the value of the store.
     * @param  mixed $value
     * @return void
     */
    public function set(mixed $value): void {
        $this->value = $value;
        for ($this->callbacks->rewind(); $this->callbacks->valid(); $this->callbacks->next()) {
            /** @var Closure $callback */
            $callback = $this->callbacks->current();
            ($callback)($this->value);
        }
    }

    /**
     * @param  Closure(mixed):mixed $callback
     * @return void
     */
    public function update(Closure $callback):void {
        $value = ($callback)($this->value);
        $this->set($value);
    }


    /**
     * Subscribe to this store and get notified of every update.
     * @param  Closure        $callback callback executed whenever there's an update,
     *                                  it takes 1 parameter, the new value of the store.
     * @return Closure():void a function that cancels this subscription.
     */
    public function subscribe(Closure $callback): Closure {
        $this->callbacks->push($callback);
        
        ($callback)($this->value);

        return function() use ($callback):void {
            $this->unsubscribe($callback);
        };
    }

    private function unsubscribe(Closure $callback):void {
        for ($this->callbacks->rewind(); $this->callbacks->valid(); $this->callbacks->next()) {
            if ($this->callbacks->current() === $callback) {
                $this->callbacks->offsetUnset($this->callbacks->key());
                return;
            }
        }
    }
}
