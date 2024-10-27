<?php

namespace CatPaw\Schedule\Implementations\Schedule;

use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\deferred;
use function CatPaw\Core\error;

use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Schedule\Interfaces\ScheduleInterface;
use CatPaw\Schedule\ScheduleConfiguration;
use CatPaw\Schedule\ScheduleEntry;
use DateTime;
use DateTimeZone;
use Revolt\EventLoop;
use Throwable;

#[Provider]
readonly class SimpleSchedule implements ScheduleInterface {
    private const PATTERN_AFTER = '/\s*after\s+([0-9])+\s+(minutes|seconds|hours|months|years|minute|second|hour|month|year)\s*/i';
    private const PATTERN_DAILY = '/\s*daily\s+at\s+([0-9]{1,2})[:.]?([0-9]{0,2})[:.]?([0-9]{0,2})(\s+AM|PM)?\s*/i';
    private DateTimeZone $timezone;

    public function __construct() {
        $systemTimeZone = system('date +%Z');
        $this->timezone = new DateTimeZone($systemTimeZone);
    }

    /**
     * Create a date using the scheduler's internal timezone.
     * @param  mixed    $value the same value you would pass to `new DateTime($value)`.
     * @return DateTime a new `DateTime` that's using the scheduler's timezone.
     */
    public function date(mixed $value = 'now'):DateTime {
        return new DateTime($value, $this->timezone);
    }

    /**
     * @param  string                  $due
     * @param  callable(callable):void $function
     * @return Result<ScheduleEntry>
     */
    public function after(string $due, callable $function):Result {
        $scheduleConfiguration = $this->configure(format: "after $due", repeat: false)->unwrap($error);
        if ($error) {
            return error($error);
        }

        return $this->schedule(
            function: $function,
            scheduleConfiguration: $scheduleConfiguration,
        );
    }

    /**
     * @param  string                  $due
     * @param  callable(callable):void $function
     * @return Result<ScheduleEntry>
     */
    public function every(string $due, callable $function):Result {
        $scheduleConfiguration = $this->configure(format: "after $due", repeat: true)->unwrap($error);
        if ($error) {
            return error($error);
        }

        return $this->schedule(
            function: $function,
            scheduleConfiguration: $scheduleConfiguration,
        );
    }    /**
     * @param  string                  $due
     * @param  callable(callable):void $function
     * @return Result<ScheduleEntry>
     */
    public function daily(string $due, callable $function):Result {
        $scheduleConfiguration = $this->configure(format: "daily $due", repeat: true)->unwrap($error);
        if ($error) {
            return error($error);
        }

        return $this->schedule(
            function: $function,
            scheduleConfiguration: $scheduleConfiguration,
        );
    }

    /**
     * @param  string                        $format
     * @param  bool                          $repeat
     * @return Result<ScheduleConfiguration>
     */
    private function configure(string $format, bool $repeat):Result {
        if (preg_match(self::PATTERN_AFTER, $format, $matches)) {
            [,$value,$humanReadableUnit] = $matches;
            $multiplier                  = match ($humanReadableUnit) {
                'year','years' => 60   * 60 * 24 * 365,
                'month','months' => 60 * 60 * 24 * 30,
                'week','weeks' => 60   * 60 * 24 * 7,
                'day','days' => 60     * 60 * 24,
                'hour','hours' => 60   * 60,
                'minute','minutes' => 60,
                'second','seconds' => 1,
                default => -1,
            };

            if (-1 === $multiplier) {
                return error("Invalid time unit `$humanReadableUnit`.");
            }

            return ok(ScheduleConfiguration::create(value: ((int)$value) * $multiplier, repeat: $repeat));
        }

        if (preg_match(self::PATTERN_DAILY, $format, $matches)) {
            $hour   = (int)($matches[1]);
            $minute = (int)($matches[2]);
            $second = (int)($matches[3]);

            if ($minute > 59) {
                return error("Invalid time format `$format`, minute can't be greater than 59.");
            }
            
            if ($second > 59) {
                return error("Invalid time format `$format`, second can't be greater than 59.");
            }

            $am = strtoupper(trim($matches[4] ?? '')) === 'AM';
            $pm = strtoupper(trim($matches[4] ?? '')) === 'PM';

            if ($am || $pm) {
                if (12 === $hour && $minute > 0) {
                    return error("Invalid time format `$format`, minute can't be greater than 0 when hour is 12.");
                } else if ($hour > 12) {
                    return error("Invalid time format `$format`, hour can't be greater than 12.");
                }
            } else if ($hour > 23) {
                return error("Invalid time format `$format`, hour can't be greater than 23.");
            }

            if ($am) {
                // @phpstan-ignore-next-line
                if (12 === $hour && $minute <= 59) {
                    $hour = 0;
                }
            } else if ($pm) {
                // @phpstan-ignore-next-line
                if ($hour >= 1 && $hour <= 12 && $minute <= 59) {
                    $hour = $hour + 12;
                }
            }
            try {
                $now       = new DateTime('now', $this->timezone);
                $requested = new DateTime('now', $this->timezone);
                $requested->setTime($hour, $minute, $second);
            } catch(Throwable $e) {
                return error($e);
            }

            $value = $requested->getTimestamp() - $now->getTimestamp();
            if ($value < 0) {
                $value = 0;
            }
            return ok(ScheduleConfiguration::create(value: $value, repeat: $repeat));
        }
        return error("Invalid due pattern.");
    }

    /**
     * Schedule a function to execute.
     * @param  callable(callable):void $function              the function to execute.
     * @param  ScheduleConfiguration   $scheduleConfiguration
     * @return Result<ScheduleEntry>
     */
    private function schedule(
        callable $function,
        ScheduleConfiguration $scheduleConfiguration,
    ):Result {
        $delta      = $scheduleConfiguration->value;
        $semaphore  = deferred();
        $callbackId = '';

        $cancel = static function() use (&$callbackId, $semaphore) {
            EventLoop::cancel($callbackId);
            $semaphore->complete();
        };

        $runner = static function() use ($function, $cancel) {
            $function($cancel);
        };

        if ($scheduleConfiguration->repeat) {
            $callbackId = EventLoop::repeat($delta, $runner);
        } else {
            $callbackId = EventLoop::delay($delta, static function() use ($runner, $semaphore) {
                $runner();
                $semaphore->complete();
            });
        }

        return ok(ScheduleEntry::create(
            callbackId: $callbackId,
            future: $semaphore->getFuture(),
            scheduleConfiguration: $scheduleConfiguration,
        ));
    }
}
