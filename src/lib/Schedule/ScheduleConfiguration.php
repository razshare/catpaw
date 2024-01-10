<?php
namespace CatPaw\Schedule;

readonly class ScheduleEntry {
    public static function create(int $value, int $multiplier): ScheduleEntry {
        return new self(value:$value, multiplier:$multiplier);
    }

    private function __construct(
        public int $value,
        public int $multiplier,
    ) {
    }
}