<?php
namespace CatPaw\Core;

use Closure;
use SplDoublyLinkedList;

/**
 * @template T
 * @package CatPaw
 */
/** @psalm-suppress MissingTemplateParam */
class LinkedList extends SplDoublyLinkedList {
    private function __construct() {
    }

    /**
     * @template T
     * @return LinkedList<T>
     */
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