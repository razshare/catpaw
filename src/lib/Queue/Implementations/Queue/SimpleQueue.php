<?php

namespace CatPaw\Queue\Implementations\Queue;

use function Amp\async;
use Amp\Future;
use function Amp\Future\awaitAll;
use CatPaw\Core\Attributes\Provider;
use CatPaw\Queue\Interfaces\QueueInterface;
use Closure;
use Error;
use SplDoublyLinkedList;
use SplQueue;

#[Provider]
class SimpleQueue implements QueueInterface {
    /**
     * Map of queues.
     * @var array<string,SplQueue<callable>>
     */
    private array $queues = [];

    /**
     * 
     * @param  string   $tag
     * @param  callable $action
     * @throws Error
     * @return void
     */
    public function queue(string $tag, callable $action):void {
        if ($this->consuming) {
            $this->consumer->await();
        }

        if (!isset($this->queues[$tag])) {
            $this->queues[$tag] = new SplQueue;
        }
        $this->queues[$tag]->enqueue($action);
    }

    private bool $consuming = false;

    /** @var Future<mixed> */
    private null|Future $consumer = null;

    /**
     * Consume all available callables on all tags.
     * Will not execute a second time unless the previous execution has finished.
     * @return void
     */
    public function consume():void {
        if ($this->consuming) {
            $this->consumer->await();
            $this->consume();
        }
        $this->consumer = async(function() {
            $this->consuming = true;
            $promises        = [];
            foreach ($this->queues as $tag => $queue) {
                $queue = $this->queues[$tag];
                $queue->setIteratorMode(SplDoublyLinkedList::IT_MODE_DELETE);
                $promises[] = async(function() use ($queue, $tag) {
                    for ($queue->rewind(); $queue->valid(); $queue->next()) {
                        /** @var Closure $action */
                        $action = $queue->current();
                        $action($tag);
                    }
                });
            }
            awaitAll($promises);
            $this->consuming = false;
        });
        $this->consumer->await();
    }
}
