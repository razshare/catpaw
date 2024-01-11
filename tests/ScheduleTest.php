<?php
namespace Tests;

use function CatPaw\Core\anyError;
use CatPaw\Core\Container;

use CatPaw\Core\Signal;
use CatPaw\Schedule\Services\ScheduleService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;

class ScheduleTest extends TestCase {
    public function testAll() {
        $loadAttempt = Container::load('./src/lib/');
        $this->assertFalse($loadAttempt->error);
        $unsafe = anyError(
            Container::run($this->scheduleDaily(...)),
            Container::run($this->scheduleAfter1Second(...)),
            Container::run($this->scheduleEvery1Second3Times(...)),
        );
        $this->assertFalse($unsafe->error);
        EventLoop::run();
    }

    private function scheduleDaily(ScheduleService $schedule): void {
        $hour            = date('H');
        $value           = false;
        $scheduleAttempt = $schedule->daily(
            due:"at $hour:00",
            function: function(callable $cancel) use (&$value) {
                $value = true;
                $cancel();
            }
        );
        $this->assertFalse($scheduleAttempt->error);
        $scheduleAttempt->value->future->await();
        $this->assertTrue($value);
    }
    
    
    private function scheduleAfter1Second(ScheduleService $schedule, LoggerInterface $logger): void {
        $signal  = Signal::create();
        $checked = false;
        $start   = time();
        $signal->listen(function() use ($start, &$checked) {
            $end   = time();
            $delta = $end - $start;
            // 50ms margin
            $ok      = $delta < 50 && $delta > -50;
            $checked = true;
            $this->assertTrue($ok);
        });
        $error = $schedule->after('1 second', static function() use ($signal, $logger) {
            $logger->info("Function executed after 1 second.");
            $signal->sigterm();
        })->error;
        $this->assertFalse($error);
        EventLoop::delay(1.1, function() use (&$checked) {
            $this->assertTrue($checked);
        });
    }
    
    public function scheduleEvery1Second3Times(ScheduleService $schedule, LoggerInterface $logger): void {
        $value = 0;
        $logger->info("Executing function every 1 second 3 times...");
        $entry = $schedule->every("1 seconds", static function(callable $cancel) use (&$value, $logger) {
            $iteration = $value + 1;
            $logger->info("Scheduled execution number $iteration");
            $value++;
            if (3 === $value) {
                $cancel();
            }
        });
        $this->assertFalse($entry->error);
        $entry->value->future->await();
        $logger->info("Done executing.");
        $this->assertEquals(3, $value);
    }
}
