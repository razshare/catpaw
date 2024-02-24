<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use function CatPaw\Core\env;
use CatPaw\Core\File;

use CatPaw\Core\Services\EnvironmentService;
use CatPaw\Core\Signal;

use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase {
    public function testAll() {
        Container::load(asFileName(__DIR__, '../src/lib'))->try($error);
        $this->assertFalse($error);
        anyError(function() {
            yield Container::run($this->makeSureEnvWorks(...));
            yield Container::run($this->makeSureUnsafeWorks(...));
            yield Container::run($this->makeSureUnsafeWorksWithAnyError(...));
            yield Container::run($this->makeSureSignalsWork(...));
        })->try($error);
        $this->assertFalse($error);
    }


    private function makeSureEnvWorks(EnvironmentService $service): void {
        $service->setFileName(asFileName(__DIR__, 'env.yaml'));
        $service->load()->try($error);
        $this->assertFalse($error);
        $sayHello = env("say.hello");
        $this->assertEquals('hello world', $sayHello);
        $service->set("test-key", "test-value");
        $test = env("test-key");
        $this->assertEquals('test-value', $test);
    }

    public function makeSureUnsafeWorks() {
        // open file
        $file = File::open(asFileName(__DIR__, 'file.txt'))->try($error);
        $this->assertFalse($error);

        // read contents
        $contents = $file->readAll()->await()->try($error);
        $this->assertFalse($error);

        $this->assertEquals("hello\n", $contents);

        // close file
        $file->close();

        echo $contents.PHP_EOL;
    }

    public function makeSureUnsafeWorksWithAnyError() {
        $contents = anyError(function() {
            // open file
            $file = File::open(asFileName(__DIR__, 'file.txt'))->try($error)
                or yield $error;

            // read contents
            $contents = $file->readAll()->await()->try($error)
            or yield $error;

            // close file
            $file->close();

            return $contents;
        })->try($error);

        $this->assertFalse($error);

        $this->assertEquals("hello\n", $contents);

        echo $contents.PHP_EOL;
    }

    public function makeSureSignalsWork() {
        $signal  = Signal::create();
        $counter = 0;
        $signal->listen(function() use (&$counter) {
            $counter++;
        });
        $signal->send();
        $this->assertEquals(1, $counter);
    }
}
