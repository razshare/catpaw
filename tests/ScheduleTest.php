<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;

use CatPaw\Core\Container;
use CatPaw\Core\Signal;
use CatPaw\Schedule\Services\ScheduleService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;

class ScheduleTest extends TestCase {
    public function testAll():void {
        Container::provideDefaults("Test")->unwrap($error);
        $this->assertNull($error);
        Container::load(asFileName(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->scheduleDaily(...));
            yield Container::run($this->scheduleAfter1Second(...));
            yield Container::run($this->scheduleEvery1Second3Times(...));
        })->unwrap($error);
        $this->assertNull($error);
        EventLoop::run();
    }

    private function scheduleDaily(ScheduleService $scheduler): void {
        $hour     = $scheduler->date()->format('H');
        $value    = false;
        $schedule = $scheduler->daily(
            due:"at $hour:00",
            function: function(callable $cancel) use (&$value) {
                $value = true;
                $cancel();
            }
        )->unwrap($error);
        $this->assertNull($error);
        $schedule->future->await();
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
        $schedule->after('1 second', static function() use ($signal, $logger) {
            $logger->info("Function executed after 1 second.");
            $signal->send();
        })->unwrap($error);
        $this->assertNull($error);
        EventLoop::delay(1.1, function() use (&$checked) {
            $this->assertTrue($checked);
        });
    }

    public function scheduleEvery1Second3Times(ScheduleService $scheduler, LoggerInterface $logger): void {
        $value = 0;
        $logger->info("Executing function every 1 second 3 times...");
        $schedule = $scheduler->every("1 seconds", static function(callable $cancel) use (&$value, $logger) {
            $iteration = $value + 1;
            $logger->info("Scheduled execution number $iteration");
            $value++;
            if (3 === $value) {
                $cancel();
            }
        })->unwrap($error);
        $this->assertNull($error);
        $schedule->future->await();
        $logger->info("Done executing.");
        $this->assertEquals(3, $value);
    }
}
