<?php
namespace Tests;

use function Amp\async;
use function Amp\delay;
use function CatPaw\Core\anyError;
use CatPaw\Core\Container;
use CatPaw\Core\FileName;
use function CatPaw\Store\readable;
use function CatPaw\Store\writable;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

class StoreTest extends TestCase {
    public function testAll():void {
        Container::requireLibraries(FileName::create(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        Container::loadDefaultProviders("Test")->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->basic(...));
            yield Container::run($this->multipleSubscribers(...));
            yield Container::run($this->withDelay(...));
            yield Container::run($this->set(...));
            yield Container::run($this->subscribe(...));
            yield Container::run($this->update(...));
            yield Container::run($this->makingSureCleanUpFunctionIsInvoked(...));
        })->unwrap($error);
        $this->assertNull($error);
        EventLoop::run();
    }

    private function basic():void {
        $store = readable("hello", function($set) {
            delay(.5);
            $set("hello world");
            return function() { };
        });


        $unsubscribe = $store->subscribe(function($value) {
            $this->assertEquals("hello", $value);
        });
        $unsubscribe();

        delay(.6);
        $unsubscribe = $store->subscribe(function($value) {
            $this->assertEquals("hello world", $value);
        });
        $unsubscribe();
    }

    private function multipleSubscribers():void {
        $unsubscribers = [];

        $value1 = '';
        $value2 = '';
        $value3 = '';

        $counter = writable(0);

        $unsubscribeAll = function() use (&$unsubscribers) {
            // @phpstan-ignore-next-line
            foreach ($unsubscribers as $unsubscribe) {
                $unsubscribe();
            }
        };

        $store = readable("default", function($set) use (
            &$value1,
            &$value2,
            &$value3,
        ) {
            $set("hello world");
            return function() use (
                &$value1,
                &$value2,
                &$value3,
            ) {
                $this->assertEquals("hello world", $value1);
                $this->assertEquals("hello world", $value2);
                $this->assertEquals("hello world", $value3);

                echo "All subscribers have unsubscribed\n";
            };
        });


        $unsubscribers[] = $store->subscribe(function($value) use ($counter, &$value1) {
            echo "new value received: $value".PHP_EOL;
            $value1 = $value;
            $counter->set($counter->get() + 1);
        });

        $unsubscribers[] = $store->subscribe(function($value) use ($counter, &$value2) {
            echo "new value received: $value".PHP_EOL;
            $value2 = $value;
            $counter->set($counter->get() + 1);
        });

        $unsubscribers[] = $store->subscribe(function($value) use ($counter, &$value3) {
            echo "new value received: $value".PHP_EOL;
            $value3 = $value;
            $counter->set($counter->get() + 1);
        });

        $counter->subscribe(fn ($counter) => $counter >= 6?$unsubscribeAll():false);
    }

    private function makingSureCleanUpFunctionIsInvoked():void {
        $cleanedUp = false;
        $store     = readable("default", function($set) use (&$cleanedUp) {
            $set("hello world");
            return function() use (&$cleanedUp) {
                echo "All subscribers have unsubscribed\n";
                $cleanedUp = true;
            };
        });
        $unsubscribe = $store->subscribe(function($value) {
            echo "new value: $value\n";
        });

        $unsubscribe();

        $this->assertEquals(true, $cleanedUp);
    }

    private function withDelay():void {
        $cleanedUp = false;
        $store     = readable("default", function($set) use (&$cleanedUp) {
            $set("hello world");
            return function() use (&$cleanedUp) {
                echo "All subscribers have unsubscribed\n";
                $cleanedUp = true;
            };
        });

        $value1 = '';
        $value2 = '';
        $value3 = '';

        $unsubscribe1 = $store->subscribe(function($value) use (&$value1) {
            if ('default' === $value) {
                return;
            }
            $value1 = $value;
            echo "new value received: $value".PHP_EOL;
        });

        $unsubscribe2 = $store->subscribe(function($value) use (&$value2) {
            if ('default' === $value) {
                return;
            }
            $value2 = $value;
            echo "new value received: $value".PHP_EOL;
        });

        $unsubscribe3 = $store->subscribe(function($value) use (&$value3) {
            if ('default' === $value) {
                return;
            }
            $value3 = $value;
            echo "new value received: $value".PHP_EOL;
        });

        async(function() use (
            $unsubscribe1,
            $unsubscribe2,
            $unsubscribe3,
            &$value1,
            &$value2,
            &$value3,
        ) {
            delay(.5);
            $unsubscribe1();
            $unsubscribe2();
            $unsubscribe3();

            $this->assertEquals("hello world", $value1);
            $this->assertEquals("hello world", $value2);
            $this->assertEquals("hello world", $value3);
        });
    }

    private function set():void {
        $store = writable("hello");
        $this->assertEquals("hello", $store->get());
        $store->set("hello world");
        $this->assertEquals("hello world", $store->get());
    }

    private function subscribe():void {
        $startTime = time();
        delay(1);
        $store = writable(time());
        $store->subscribe(fn ($now) => $this->assertGreaterThan($startTime, $now));
        $store->set(time());
    }

    private function update():void {
        $store       = writable(0);
        $unsubscribe = $store->subscribe(function($value) {
            $this->assertEquals(0, $value);
        });
        $unsubscribe();
        $store->update(fn (&$value) => ++$value);
        $unsubscribe = $store->subscribe(function($value) {
            $this->assertEquals(1, $value);
        });
        $unsubscribe();
    }
}
