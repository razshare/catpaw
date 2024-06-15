<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use function CatPaw\Core\env;
use CatPaw\Core\File;
use function CatPaw\Core\goffi;
use CatPaw\Core\Interfaces\EnvironmentInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Signal;
use CatPaw\Core\Unsafe;

use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase {
    public function testAll():void {
        Container::requireLibraries(asFileName(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        Container::loadDefaultProviders("Test")->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->makeSureEnvWorks(...));
            yield Container::run($this->makeSureUnsafeWorks(...));
            yield Container::run($this->makeSureUnsafeWorksWithAnyError(...));
            yield Container::run($this->makeSureSignalsWork(...));
            yield Container::run($this->makeSureGoffiWorks(...));
        })->unwrap($error);
        $this->assertNull($error);
    }


    private function makeSureEnvWorks(EnvironmentInterface $environment): void {
        $environment->setFileName(asFileName(__DIR__, 'env.ini'));
        $environment->load()->unwrap($error);
        $this->assertNull($error);
        $sayHello = env("say.hello");
        $this->assertEquals('hello world', $sayHello);
        $environment->set("test-key", "test-value");
        $test = env("test-key");
        $this->assertEquals('test-value', $test);
    }

    public function makeSureUnsafeWorks():void {
        // open file
        $file = File::open(asFileName(__DIR__, 'file.txt'))->unwrap($error);
        $this->assertNull($error);

        // read contents
        $contents = $file->readAll()->unwrap($error);
        $this->assertNull($error);

        $this->assertEquals("hello\n", $contents);

        // close file
        $file->close();

        echo $contents.PHP_EOL;
    }

    public function makeSureUnsafeWorksWithAnyError():void {
        $contents = anyError(function() {
            // open file
            $file = File::open(asFileName(__DIR__, 'file.txt'))->try();

            // read contents
            $contents = $file->readAll()->try();

            // close file
            $file->close();

            return $contents;
        })->unwrap($error);

        $this->assertNull($error);

        $this->assertEquals("hello\n", $contents);

        echo $contents.PHP_EOL;
    }

    public function makeSureSignalsWork():void {
        $signal  = Signal::create();
        $counter = 0;
        $signal->listen(function() use (&$counter) {
            $counter++;
        });
        $signal->send();
        $this->assertEquals(1, $counter);
    }

    /**
     * @return Unsafe<None>
     */
    public function makeSureGoffiWorks():Unsafe {
        return anyError(function() {
            $lib    = goffi(Contract::class, asFileName(__DIR__, './main.so'))->try();
            $result = $lib->hello("world");
            $this->assertEquals("hello world", $result);
            return ok();
        });
    }
}

interface Contract {
    public function hello(string $name):string;
}
