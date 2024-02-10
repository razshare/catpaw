<?php
namespace Tests;

// use function Amp\delay;
// use function CatPaw\Core\anyError;
// use CatPaw\Core\Container;
// use function CatPaw\Core\error;
// use CatPaw\Core\Unsafe;
// use CatPaw\RaspberryPi\Services\GpioService;
use PHPUnit\Framework\TestCase;
// use Revolt\EventLoop;

class RaspberryPiTest extends TestCase {
    // public function testAll(): void {
    //     $this->assertTrue(true);
    //     Container::load(asFileName(__DIR__,'../src/lib'))->try($error);
    //     $this->assertFalse($error);
    //     anyError(function() {
    //         yield Container::run($this->blink(...));
    //     })->try($error);
    //     $this->assertFalse($error);
    //     EventLoop::run();
    // }

    // public function blink(GpioService $gpio): Unsafe {
    //     $writer = $gpio->createWriter('12');
    //     $active = false;
    //     while (true) {
    //         delay(1);
    //         $active = !$active;
    //         $writer->write($active?'1':'0')->try($error);
    //         if ($error) {
    //             return error($error);
    //         }
    //     }
    // }
}
