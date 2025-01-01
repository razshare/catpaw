<?php
namespace CatPaw\Schedule;

readonly class ScheduleConfiguration {
    public function __construct(
        public int $value,
        public bool $repeat,
    ) {
    }
}