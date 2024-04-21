<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;

use CatPaw\Core\Unsafe;
use CatPaw\Superstyle\Superstyle;
use PHPUnit\Framework\TestCase;

class SuperstyleTest extends TestCase {
    public function testAll() {
        Container::load(asFileName(__DIR__, '../src/lib'))->try($error);
        $this->assertFalse($error);
        anyError(function() {
            yield Container::run($this->makeSureSuperstyleServiceWorks(...));
        })->try($error);
        $this->assertFalse($error);
    }


    private function makeSureSuperstyleServiceWorks(): Unsafe {
        return anyError(function() {
            $result = Superstyle::parse(asFileName(__DIR__, './superstyle.scss'), ["value" => "world"])->try($error) or yield $error;
            $this->assertEquals("<main><button>click me world</button></main>", $result);
            print_r($result);
        });
    }
}
