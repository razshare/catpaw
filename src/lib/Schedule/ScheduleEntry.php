<?php

namespace CatPaw\Schedule;

use Amp\Future;
use Revolt\EventLoop;

readonly class ScheduleEntry {
    public static function create(
        string $callbackId,
        Future $future,
        ScheduleConfiguration $scheduleConfiguration,
    ): ScheduleEntry {
        return new self(
            callbackId: $callbackId,
            future: $future,
            scheduleConfiguration: $scheduleConfiguration,
        );
    }
    
    private function __construct(
        private string $callbackId,
        public Future $future,
        public ScheduleConfiguration $scheduleConfiguration,
    ) {
    }
    
    public function cancel(): void {
        EventLoop::cancel($this->callbackId);
    }
}