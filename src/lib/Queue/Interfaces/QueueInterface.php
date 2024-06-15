<?php
namespace CatPaw\Queue\Interfaces;

interface QueueInterface {
    /**
     * Queue a callable to run on a specific tag.
     * @param  string   $tag
     * @param  callable $action
     * @return void
     */
    public function queue(string $tag, callable $action):void ;

    /**
     * Consume all available callables on all tags.
     * Will not execute a second time unless the previous execution has finished.
     * @return void
     */
    public function consume():void;
}