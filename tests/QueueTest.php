<?php
namespace Tests;

use function Amp\delay;
use function CatPaw\Core\anyError;
use CatPaw\Core\Container;
use CatPaw\Core\FileName;
use CatPaw\Queue\Interfaces\QueueInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class QueueTest extends TestCase {
    public function testAll():void {
        Container::requireLibraries(FileName::create(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        Container::loadDefaultProviders("Test")->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->execution(...));
            yield Container::run($this->tag(...));
            yield Container::run($this->order(...));
            yield Container::run($this->timedQueue(...));
        })->unwrap($error);
        $this->assertNull($error);
    }

    private function execution(QueueInterface $queue):void {
        $executed = false;
        $queue->queue("my-tag", function() use (&$executed) {
            $executed = true;
        });
        $queue->consume();
        $this->assertTrue($executed);
    }

    private function tag(QueueInterface $queue):void {
        $queue->queue("my-tag", function($tag) {
            $this->assertEquals("my-tag", $tag);
        });
        $queue->consume();
        echo "DONE\n";
    }

    private function order(QueueInterface $queue):void {
        /** @var array<mixed> */
        $stack = [];
        $queue->queue("my-tag-1", function($tag) use (&$stack) {
            $stack[] = $tag;
        });
        $queue->queue("my-tag-2", function($tag) use (&$stack) {
            $stack[] = $tag;
        });

        $queue->consume();

        $this->assertArrayHasKey(0, $stack);
        $this->assertArrayHasKey(1, $stack);

        $this->assertEquals("my-tag-1", $stack[0]);
        $this->assertEquals("my-tag-2", $stack[1]);
    }

    private function timedQueue(QueueInterface $queue, LoggerInterface $logger):void {
        $queue->queue("my-tag-1", function($tag) use ($logger) {
            delay(.005);
            $logger->info("Executed $tag");
        });
        $queue->queue("my-tag-2", function($tag) use ($logger) {
            delay(.100);
            $logger->info("Executed $tag (A)");
        });
        $queue->queue("my-tag-2", function($tag) use ($logger) {
            delay(.030);
            $logger->info("Executed $tag (B)");
        });
        $queue->queue("my-tag-3", function($tag) use ($logger) {
            $logger->info("Executed $tag");
        });
        $queue->queue("my-tag-4", function($tag) use ($logger) {
            $logger->info("Executed $tag");
        });
        $start = floor(microtime(true) * 1000);
        $queue->consume();
        $end       = floor(microtime(true) * 1000);
        $execution = ($end - $start);
        $logger->info("Queue stopped after $execution milliseconds.");

        $this->assertGreaterThanOrEqual(100 - 50, $execution);
        $this->assertLessThanOrEqual(100 + 50, $execution);
    }
}