<?php
namespace CatPaw\Utilities;

use Closure;
use SplDoublyLinkedList;

/**
 * @template T
 * @package CatPaw\Utilities
 */
class LinkedList extends SplDoublyLinkedList {
    private function __construct() {
    }

    /** @return LinkedList<T>  */
    public static function create():self {
        return new self();
    }

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