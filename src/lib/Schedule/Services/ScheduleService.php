<?php

namespace CatPaw\Schedule\Services;

use CatPaw\Attributes\Service;
use function CatPaw\error;
use function CatPaw\ok;
use CatPaw\Unsafe;
use Closure;

use Error;
use Revolt\EventLoop;

#[Service]
class ScheduleService {
    private const PATTERN = '/in\s+([0-9])+\s+(minutes|seconds|hours|months|years|minute|second|hour|month|year)/i';

    /**
     * @param  string       $due      a human readable string pattern indicating the due time.<br/>
     *                                Example: <br/>
     *                                - `in 2 minutes`
     *                                - `in 1 week`
     *                                - `in 3 months`
     *                                - `in 1 year`
     * @param  Closure      $function
     * @throws Error        if the `$due` pattern is invalid.
     * @return Unsafe<void>
     */
    public function schedule(
        string $due,
        Closure $function,
    ):Unsafe {
        if (!preg_match(self::PATTERN, $due, $matches)) {
            return error("Invalid due pattern.");
        }
        
        [,$value,$unit] = $matches;

        $unit = match ($unit) {
            'year','years' => 60   * 60 * 24 * 365,
            'month','months' => 60 * 60 * 24 * 30,
            'week','weeks' => 60   * 60 * 24 * 7,
            'day','days' => 60     * 60 * 24,
            'hour','hours' => 60   * 60,
            'minute','minutes' => 60,
            'second','seconds' => 1,
            default => 1,
        };

        if (is_string($unit)) {
            return error("Invalid due unit ($unit).");
        }

        if (!is_numeric($value)) {
            return error("Invalid due value ($value).");
        }

        $value = (int)$value;
        
        $delta = $value * $unit;

        $delta = EventLoop::delay($delta, static function() use ($function) {
            $function();
        });

        return ok();
    }
}