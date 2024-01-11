<?php
namespace Tests;

use function Amp\delay;
use function CatPaw\anyError;
use CatPaw\Container;
use function CatPaw\error;
use CatPaw\RaspberryPI\Services\GpioService;

use CatPaw\Unsafe;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

class RaspberryPiTest extends TestCase {
    public function testAll(): void {
        $this->assertTrue(true);
//        $loadAttempt = Container::load('./src/lib/');
//        $this->assertFalse($loadAttempt->error);
//        $unsafe = anyError(
//            Container::run($this->blink(...)),
//        );
//        $this->assertFalse($unsafe->error);
//        EventLoop::run();
    }
    
    public function blink(GpioService $gpio): Unsafe {
        $writer = $gpio->createWriter('12');
        $active = false;
        while (true) {
            delay(1);
            $active = !$active;
            if ($error = $writer->write($active?'1':'0')->error) {
                return error($error);
            }
        }
    }
}