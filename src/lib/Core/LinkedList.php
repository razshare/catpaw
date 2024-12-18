<?php
namespace CatPaw\Core;

use Closure;
use SplDoublyLinkedList;

/**
 * @template T
 * @package CatPaw
 * @extends SplDoublyLinkedList<T>
 */
class LinkedList extends SplDoublyLinkedList {
    /**
     * Iterate the linked list.
     * @param int     $mode iteration mode (lookup constants).
     * @param Closure $call iteration callback.
     *                      return void
     */
    public function iterate(int $mode, Closure $call):void {
        $this->setIteratorMode($mode);
        for ($this->rewind();$this->valid();$this->next()) {
            $obj = $this->current();
            $call($obj);
        }
    }
}
