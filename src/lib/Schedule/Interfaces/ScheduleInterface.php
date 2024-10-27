<?php
namespace CatPaw\Schedule\Interfaces;

use CatPaw\Core\Result;
use CatPaw\Schedule\ScheduleEntry;
use DateTime;

interface ScheduleInterface {
    /**
     * Create a date using the scheduler's internal timezone.
     * @param  mixed    $value The date, as in - the same value you would pass to `new DateTime($value)`.
     * @return DateTime A new `DateTime` that's using the scheduler's timezone.
     */
    public function date(mixed $value = 'now'):DateTime;

    /**
     * @param  string                  $due      When the schedule should trigger.
     * @param  callable(callable):void $function The function to execute when the schedule triggers.
     * @return Result<ScheduleEntry>
     */
    public function after(string $due, callable $function):Result;

    /**
     * @param  string                  $due      When the schedule should trigger.
     * @param  callable(callable):void $function The function to execute when the schedule triggers.
     * @return Result<ScheduleEntry>
     */
    public function every(string $due, callable $function):Result;

    /**
     * @param  string                  $due      When the schedule should trigger.
     * @param  callable(callable):void $function The function to execute when the schedule triggers.
     * @return Result<ScheduleEntry>
     */
    public function daily(string $due, callable $function):Result;
}