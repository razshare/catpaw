<?php

namespace CatPaw\Store;

use function Amp\async;
use function CatPaw\Core\tick;

use Closure;

use SplDoublyLinkedList;

class Readable {
    /**
     * @param mixed                 $value   initial value of the store
     * @param Closure(Closure):void $onStart a function that will be executed when the
     *                                       first subscriber subscribes to the store.
     * 
     *                                              The function should (but it's not required to) return another function, which 
     *                                              will be executed when the last subscriber of the store unsubscribes.
     * @return self
     */
    public static function create(mixed $value, Closure $onStart):self {
        return new self($value, $onStart);
    }

    /** @var SplDoublyLinkedList<Closure> */
    protected SplDoublyLinkedList $functions;
    /** @var false|Closure */
    private mixed $stop           = false;
    private bool $firstSubscriber = true;
    /**
     * 
     * @param  mixed                 $value
     * @param  Closure(Closure):void $onStart
     * @return void
     */
    private function __construct(
        protected mixed $value,
        private readonly Closure $onStart,
    ) {
        $this->functions = new SplDoublyLinkedList();
        $this->functions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP);
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
        for ($this->functions->rewind(); $this->functions->valid(); $this->functions->next()) {
            /** @var Closure $function */
            $function = $this->functions->current();
            ($function)($this->value);
        }
    }

    
    /**
     * Subscribe to this store and get notified of every update.
     * @param  Closure(mixed $value):void $function a function that's executed whenever there's an update,
     *                                              it takes 1 parameter, the new value of the store.
     * @return Closure():void             a function that cancels this subscription.
     */
    public function subscribe(Closure $function): Closure {
        $this->functions->push($function);
        
        if ($this->firstSubscriber) {
            $this->firstSubscriber = false;
            async($this->onStart, function($value) {
                return tick()->finally(fn () => $this->set($value));
            });
        }
        
        ($function)($this->value);
        
        return function() use ($function):void {
            $this->unsubscribe($function);
        };
    }

    private function unsubscribe(Closure $function):void {
        for ($this->functions->rewind(); $this->functions->valid(); $this->functions->next()) {
            if ($this->functions->current() === $function) {
                $this->functions->offsetUnset($this->functions->key());
                if (0 === $this->functions->count()) {
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