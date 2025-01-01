<?php

namespace CatPaw\Schedule;

use Amp\Future;
use Revolt\EventLoop;

readonly class ScheduleEntry {
    /**
     *
     * @param  string                $callbackId
     * @param  Future<mixed>         $future
     * @param  ScheduleConfiguration $scheduleConfiguration
     * @return void
     */
    public function __construct(
        private string $callbackId,
        public Future $future,
        public ScheduleConfiguration $scheduleConfiguration,
    ) {
    }

    public function cancel():void {
        EventLoop::cancel($this->callbackId);
    }
}
