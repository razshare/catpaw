<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use CatPaw\Store\Interfaces\StateInterface;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

class StateTest extends TestCase {
    public function testAll():void {
        Container::requireLibraries(asFileName(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        Container::loadDefaultProviders("Test")->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->makingSureStateInterfaceWorks(...));
        })->unwrap($error);
        $this->assertNull($error);
        EventLoop::run();
    }
    
    private function makingSureStateInterfaceWorks(StateInterface $state):void {
        $test = $state->of("test");
        $test->set("test");
        $this->assertEquals("test", $test->get());
    }
}
