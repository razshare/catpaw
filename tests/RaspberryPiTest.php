<?php
namespace Tests;

use function Amp\delay;
use function CatPaw\Core\anyError;
use CatPaw\Core\Container;
use CatPaw\Core\None;

// use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\RaspberryPi\Interfaces\GpioInterface;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

class RaspberryPiTest extends TestCase {
    public function testAll(): void {
        $this->assertTrue(true);
        anyError(function() {
            yield Container::run($this->blink(...));
        })->unwrap($error);
        $this->assertNull($error);
        EventLoop::run();
    }

    /**
     *
     * @param  GpioInterface $gpio
     * @return Unsafe<None>
     */
    public function blink(GpioInterface $gpio): Unsafe {
        // $writer = $gpio->createWriter('12');
        $active = false;
        for ($i = 0; $i < 3; $i++) {
            delay(0.5);
            // $active = !$active;
            // $writer->write($active?'1':'0')->try($error);
            // if ($error) {
            //     return error($error);
            // }
        }
        return ok();
    }
}
