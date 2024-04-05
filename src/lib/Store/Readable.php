<?php
namespace CatPaw\Store;

use function CatPaw\Core\tick;
use SplDoublyLinkedList;
/**
 * @template T
 * @package CatPaw\Store
 */
class Readable {
    /**
     * @param T                       $value   initial value of the store
     * @param callable(callable):void $onStart a function that will be executed when the
     *                                         first subscriber subscribes to the store.
     *
     *                                              The function should (but it's not required to) return another function, which
     *                                              will be executed when the last subscriber of the store unsubscribes.
     * @return self
     */
    public static function create(mixed $value, mixed $onStart):self {
        return new self($value, $onStart);
    }

    /** @var SplDoublyLinkedList<callable> */
    protected SplDoublyLinkedList $functions;
    /** @var false|(callable():void) */
    private mixed $stop           = false;
    private bool $firstSubscriber = true;
    /**
     *
     * @template T
     * @param  T                       $value
     * @param  callable(callable):void $onStart
     * @return void
     */
    private function __construct(
        protected mixed $value,
        private readonly mixed $onStart,
    ) {
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
    private function set(mixed $value): void {
        $this->value = $value;
        for ($this->functions->rewind(); $this->functions->valid(); $this->functions->next()) {
            /** @var callable $function */
            $function = $this->functions->current();
            ($function)($this->value);
        }
    }


    /**
     * Subscribe to this store and get notified of every update.
     * @param  callable(T $value):void $function a function that's executed whenever there's an update,
     *                                           it takes 1 parameter, the new value of the store.
     * @return callable():void         a function that cancels this subscription.
     */
    public function subscribe($function) {
        $this->functions->push($function);

        if ($this->firstSubscriber) {
            $this->firstSubscriber = false;

            $stop = ($this->onStart)(function($value) {
                return tick()->finally(fn () => $this->set($value));
            });

            if ($stop) {
                $this->stop = $stop;
            }
        }

        ($function)($this->value);

        return function() use ($function):void {
            $this->unsubscribe($function);
        };
    }

    /**
     * @param  callable $function
     * @return void
     */
    private function unsubscribe(callable $function):void {
        for ($this->functions->rewind(); $this->functions->valid(); $this->functions->next()) {
            if ($this->functions->current() === $function) {
                $this->functions->offsetUnset($this->functions->key());
                $count = $this->functions->count();
                if (0 === $count) {
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
