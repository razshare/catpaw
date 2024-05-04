<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use CatPaw\Core\None;
use const CatPaw\Core\NONE;
use CatPaw\Core\Unsafe;
use CatPaw\Superstyle\Services\SuperstyleService;
use PHPUnit\Framework\TestCase;

class SuperstyleTest extends TestCase {
    public function testAll():void {
        Container::load(asFileName(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->makeSureSuperstyleServiceWorks(...));
        })->unwrap($error);
        $this->assertNull($error);
    }


    /**
     * @return Unsafe<None>
     */
    private function makeSureSuperstyleServiceWorks(SuperstyleService $style): Unsafe {
        return anyError(function() use ($style) {
            $result = $style->file(asFileName(__DIR__, './superstyle.hbs'), [
                "items" => [
                    "item-1",
                    "item-2",
                    "item-3",
                    "item-4",
                ],
            ])->try();
            $this->assertEquals('<main><ul><li>item-1</li><li>item-2</li><li>item-3</li><li>item-4</li></ul></main>', $result->html);
            $this->assertEquals('main {  ul { position: relative; li {   } } }', $result->css);
            print_r($result);
            return NONE;
        });
    }
}
