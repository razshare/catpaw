<?php
namespace CatPaw;

class Signal {
    private bool $busy = false;
    public static function create():self {
        return new self(LinkedList::create());
    }

    /**
     * @param LinkedList<callable(...mixed):void> $list
     */
    private function __construct(private LinkedList $list) {
    }

    /**
     * Send signal and trigger listeners.
     * @param int $code code to send, defaults to `SIGTERM`.
     * 
     */
    public function send($code = SIGTERM) {
        if ($this->busy) {
            return;
        }
        $this->busy = true;
        for ($this->list->rewind();$this->list->valid();$this->list->next()) {
            $function = $this->list->current();
            $function($code);
        }
        $this->busy = false;
    }

    /**
     * @param callable(int):void
     */
    public function listen(callable $function):void {
        $this->list->push($function);
    }

    /**
     * Clear all lsiteners.
     */
    public function clear() {
        $this->list->setIteratorMode(LinkedList::IT_MODE_DELETE);
        for ($this->list->rewind();$this->list->valid();$this->list->next()) {
            continue;
        }
    }
}