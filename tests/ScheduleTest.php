<?php
namespace Tests;

use function CatPaw\anyError;

use CatPaw\Container;
use CatPaw\Schedule\Services\ScheduleService;
use CatPaw\Signal;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

class ScheduleTest extends TestCase {
    public function testAll() {
        $loadAttempt = Container::load('./src/lib/');
        $this->assertFalse($loadAttempt->error);
        $unsafe = anyError(
            Container::run($this->scheduleIn2Seconds(...)),
        );
        $this->assertFalse($unsafe->error);
        EventLoop::run();
    }

    private function scheduleIn2Seconds(ScheduleService $schedule): void {
        $due     = Signal::create();
        $checked = false;
        $start   = time();
        $due->listen(function() use ($start, &$checked) {
            $end   = time();
            $delta = $end - $start;
            // 50ms margin
            $ok      = $delta < 50 && $delta > -50;
            $checked = true;
            $this->assertTrue($ok);
        });
        $error = $schedule->schedule('in 1 second', static fn () => $due->send())->error;
        $this->assertFalse($error);
        EventLoop::delay(1.1, function() use (&$checked) {
            $this->assertTrue($checked);
        });
    }
}
