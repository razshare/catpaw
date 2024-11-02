<?php
namespace CatPaw\Schedule;

readonly class ScheduleConfiguration {
    public static function create(int $value, bool $repeat):ScheduleConfiguration {
        return new self(value:$value, repeat: $repeat);
    }

    private function __construct(
        public int $value,
        public bool $repeat,
    ) {
    }
}