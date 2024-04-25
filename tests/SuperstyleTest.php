<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use CatPaw\Core\None;
use const CatPaw\Core\NONE;
use CatPaw\Core\Unsafe;
use CatPaw\Superstyle\Superstyle;

use PHPUnit\Framework\TestCase;

class SuperstyleTest extends TestCase {
    public function testAll():void {
        Container::load(asFileName(__DIR__, '../src/lib'))->try($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->makeSureSuperstyleServiceWorks(...));
        })->try($error);
        $this->assertNull($error);
    }


    /**
     *
     * @return Unsafe<None>
     */
    private function makeSureSuperstyleServiceWorks(): Unsafe {
        return anyError(function() {
            $result = Superstyle::parse(asFileName(__DIR__, './superstyle.scss'), ["value" => "world"])->unwrap();
            $this->assertEquals('<main><button class="btn" x-post="">click me world</button></main>', $result->html);
            $this->assertEquals('main { @apply fixed; button.btn[x-post=""] {   } }', $result->css);
            print_r($result);
            return NONE;
        });
    }
}
