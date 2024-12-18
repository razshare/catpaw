<?php
namespace CatPaw\Core;

use SplDoublyLinkedList;

class Signal {
    private bool $busy = false;

    /**
     * Create a signal.
     * @return self
     */
    public static function create():self {
        return new self(list: new LinkedList);
    }

    /**
     * @param LinkedList<callable(mixed...):void> $list
     */
    private function __construct(private readonly LinkedList $list) {
    }


    /**
     * Send signal and trigger listeners.
     */
    public function send():self {
        if ($this->busy) {
            return $this;
        }
        $this->busy = true;
        for ($this->list->rewind();$this->list->valid();$this->list->next()) {
            $function = $this->list->current();
            $function();
        }
        $this->busy = false;
        return $this;
    }

    /**
     * @param callable(int):void $function
     */
    public function listen(callable $function):self {
        // @phpstan-ignore-next-line
        $this->list->push($function);
        return $this;
    }

    /**
     * Clear all listeners.
     */
    public function clear():self {
        $this->list->setIteratorMode(SplDoublyLinkedList::IT_MODE_DELETE);
        for ($this->list->rewind();$this->list->valid();$this->list->next()) {
            continue;
        }
        return $this;
    }
}
