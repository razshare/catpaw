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
            $document = $style->file(asFileName(__DIR__, './superstyle.hbs'))->try();
            $this->assertEquals('<main><ul>{{#each items}}<li>{{.}}</li>{{/each}}</ul></main>', $document->markup);
            $this->assertEquals('main {  ul { position: relative;  } }', $document->style);
            return NONE;
        });
    }
}
