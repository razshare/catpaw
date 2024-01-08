<?php

namespace CatPaw\Store;

use function Amp\async;
use function CatPaw\deferred;

use Closure;

use SplDoublyLinkedList;

class Readable {
    /**
     * @param mixed            $value   initial value of the store
     * @param Closure(Closure) $onStart a function that will be executed when the 
     *                                  first subscriber subscribes to the store.
     * 
     *                                              The function should (but it's not required to) return another function, which 
     *                                              will be executed when the last subscriber of the store unsubscribes.
     * @return self
     */
    public static function create(mixed $value, Closure $onStart):self {
        return new self($value, $onStart);
    }

    /** @var SplDoublyLinkedList<Closure> */
    protected SplDoublyLinkedList $callbacks;
    /** @var false|Closure */
    private mixed $stop           = false;
    private bool $firstSubscriber = true;
    /**
     * 
     * @param  mixed            $value
     * @param  Closure(Closure) $onStart
     * @return void
     */
    private function __construct(
        protected mixed $value,
        private Closure $onStart,
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
    private function set(mixed $value): void {
        $this->value = $value;
        for ($this->callbacks->rewind(); $this->callbacks->valid(); $this->callbacks->next()) {
            /** @var Closure */
            $callback = $this->callbacks->current();
            ($callback)($this->value);
        }
    }

    
    /**
     * Subscribe to this store and get notified of every update.
     * @param  Closure(mixed $value) $callback a function that's executed whenever there's an update,
     *                                         it takes 1 parameter, the new value of the store.
     * @return Closure():void        a function that cancels this subscriptions.
     */
    public function subscribe(Closure $callback): Closure {
        $this->callbacks->push($callback);
        
        if ($this->firstSubscriber) {
            $this->firstSubscriber = false;
            async($this->onStart, function($value) {
                return deferred()->finally(fn () => $this->set($value));
            });
        }
        
        ($callback)($this->value);
        
        return function() use ($callback):void {
            $this->unsubscribe($callback);
        };
    }

    private function unsubscribe(Closure $callback):void {
        for ($this->callbacks->rewind(); $this->callbacks->valid(); $this->callbacks->next()) {
            if ($this->callbacks->current() === $callback) {
                $this->callbacks->offsetUnset($this->callbacks->key());
                if (0 === $this->callbacks->count()) {
                    if ($this->stop) {
                        ($this->stop)();
                    }
                    $this->firstSubscriber = true;
                }
                return;
            }
        }
    }
}