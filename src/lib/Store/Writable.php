<?php
namespace CatPaw\Store;

use Closure;
use SplDoublyLinkedList;

/**
 * @template T
 * @package CatPaw\Store
 */
class Writable {
    /**
     * @param  T    $value The initial value of the store
     * @return self
     */
    public static function create(mixed $value):self {
        return new self($value);
    }

    /** @var SplDoublyLinkedList<Closure> */
    protected SplDoublyLinkedList $functions;

    /**
     * @param  T    $value
     * @return void
     */
    private function __construct(protected mixed $value) {
        $this->functions = new SplDoublyLinkedList();
        $this->functions->setIteratorMode(SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP);
    }

    /**
     * Get the value of the store.
     * @return T
     */
    public function get(): mixed {
        return $this->value;
    }

    /**
     * Set the value of the store.
     * @param  T    $value
     * @return void
     */
    public function set(mixed $value): void {
        $this->value = $value;
        for ($this->functions->rewind(); $this->functions->valid(); $this->functions->next()) {
            /** @var callable $function */
            $function = $this->functions->current();
            ($function)($this->value);
        }
    }

    /**
     * @param  callable(T):T $function
     * @return void
     */
    public function update(callable $function):void {
        $value = ($function)($this->value);
        $this->set($value);
    }


    /**
     * Subscribe to this store and get notified of every update.
     * @param  callable(T $value):void $function callback executed whenever there's an update,
     *                                           it takes 1 parameter, the new value of the store.
     * @return callable():void         a function that cancels this subscription.
     */
    public function subscribe(callable $function): callable {
        $this->functions->push($function);

        ($function)($this->value);

        return function() use ($function):void {
            $this->unsubscribe($function);
        };
    }

    /**
     *
     * @param  callable $function
     * @return void
     */
    private function unsubscribe(callable $function):void {
        for ($this->functions->rewind(); $this->functions->valid(); $this->functions->next()) {
            if ($this->functions->current() === $function) {
                $this->functions->offsetUnset($this->functions->key());
                return;
            }
        }
    }
}
