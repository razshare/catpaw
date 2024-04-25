<?php
namespace Tests;

use function Amp\delay;
use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use CatPaw\Core\None;

// use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\RaspberryPi\Services\GpioService;
use Error;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

class RaspberryPiTest extends TestCase {
    public function testAll(): void {
        $this->assertTrue(true);
        Container::load(asFileName(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->blink(...));
        })->unwrap($error);
        $this->assertNull($error);
        EventLoop::run();
    }

    /**
     *
     * @param  GpioService  $gpio
     * @return Unsafe<None>
     */
    public function blink(GpioService $gpio): Unsafe {
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
