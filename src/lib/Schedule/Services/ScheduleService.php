<?php

namespace CatPaw\Schedule\Services;

use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Service;
use function CatPaw\deferred;
use function CatPaw\error;
use function CatPaw\ok;
use CatPaw\Schedule\ScheduleConfiguration;
use CatPaw\Schedule\ScheduleEntry;
use CatPaw\Unsafe;
use DateTime;
use DateTimeZone;
use Revolt\EventLoop;
use Throwable;


#[Service]
class ScheduleService {
    private const PATTERN_AFTER = '/\s*after\s+([0-9])+\s+(minutes|seconds|hours|months|years|minute|second|hour|month|year)\s*/i';
    private const PATTERN_DAILY = '/\s*daily\s+at\s+([0-9]{1,2})[:.]?([0-9]{0,2})[:.]?([0-9]{0,2})(\s+AM|PM)?\s*/i';

    private DateTimeZone $timezone;
    #[Entry] public function start(): Unsafe {
        try {
            $systemTimeZone = system('date +%Z');
            $this->timezone = new DateTimeZone($systemTimeZone);
            return ok();
        } catch(Throwable $e) {
            return error($e);
        }
    }
    
    /**
     * @param  string                  $due
     * @param  callable(callable):void $function
     * @return Unsafe<ScheduleEntry>
     */
    public function after(string $due, callable $function):Unsafe {
        $scheduleConfigurationAttempt = $this->configure(format: "after $due", repeat: false);
        if ($scheduleConfigurationAttempt->error) {
            return error($scheduleConfigurationAttempt->error);
        }
        $scheduleConfiguration = $scheduleConfigurationAttempt->value;
        
        return $this->schedule(
            function: $function,
            scheduleConfiguration: $scheduleConfiguration,
        );
    }
    
    /**
     * @param  string                  $due
     * @param  callable(callable):void $function
     * @return Unsafe<ScheduleEntry>
     */
    public function every(string $due, callable $function):Unsafe {
        $scheduleConfigurationAttempt = $this->configure(format: "after $due", repeat: true);
        if ($scheduleConfigurationAttempt->error) {
            return error($scheduleConfigurationAttempt->error);
        }
        $scheduleConfiguration = $scheduleConfigurationAttempt->value;
        
        return $this->schedule(
            function: $function,
            scheduleConfiguration: $scheduleConfiguration,
        );
    }    /**
     * @param  string                  $due
     * @param  callable(callable):void $function
     * @return Unsafe<ScheduleEntry>
     */
    public function daily(string $due, callable $function):Unsafe {
        $scheduleConfigurationAttempt = $this->configure(format: "daily $due", repeat: true);
        if ($scheduleConfigurationAttempt->error) {
            return error($scheduleConfigurationAttempt->error);
        }
        $scheduleConfiguration = $scheduleConfigurationAttempt->value;
        
        return $this->schedule(
            function: $function,
            scheduleConfiguration: $scheduleConfiguration,
        );
    }
    
    /**
     * @param  string                        $format
     * @param  bool                          $repeat
     * @return Unsafe<ScheduleConfiguration>
     */
    private function configure(string $format, bool $repeat):Unsafe {
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
            $hour   = (int)($matches[1] ?? date('H'));
            $minute = (int)($matches[2] ?? date('i'));
            $second = (int)($matches[3] ?? date('s'));
            
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
                if (12 === $hour && $minute <= 59) {
                    $hour = 0;
                }
            } else if ($pm) {
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
     * @return Unsafe<callable>
     */
    private function schedule(
        callable $function,
        ScheduleConfiguration $scheduleConfiguration,
    ):Unsafe {
        $delta     = $scheduleConfiguration->value;
        $semaphore = deferred();
        
        $cancel = static function() use (&$callbackId, &$scheduleConfiguration, $semaphore) {
            EventLoop::cancel($callbackId);
            $semaphore->complete();
        };

        $runner = static function() use ($function, $cancel, $semaphore) {
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