<?php
namespace CatPaw;

use SplDoublyLinkedList;

class Signal {
    private bool $busy = false;

    /**
     * Create a signal.
     * @return self
     */
    public static function create():self {
        return new self(list: LinkedList::create());
    }

    /**
     * @param LinkedList<callable(mixed...):void> $list
     */
    private function __construct(private readonly LinkedList $list) {
    }

    public function sigterm():self {
        return $this->send(SIGTERM);
    }
    /**
     * Send signal and trigger listeners.
     * @param int $code code to send, defaults to <b>0</b>.
     * 
     */
    public function send(int $code = 0):self {
        if ($this->busy) {
            return $this;
        }
        $this->busy = true;
        for ($this->list->rewind();$this->list->valid();$this->list->next()) {
            $function = $this->list->current();
            $function($code);
        }
        $this->busy = false;
        return $this;
    }

    /**
     * @param callable(int):void $function
     */
    public function listen(callable $function):self {
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