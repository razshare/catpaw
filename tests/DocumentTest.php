<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use CatPaw\Core\Implementations\Command\SimpleCommand;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Document\Interfaces\DocumentInterface;
use PHPUnit\Framework\TestCase;

class DocumentTest extends TestCase {
    public function testAll():void {
        Container::provide(CommandInterface::class, new SimpleCommand);
        Container::requireLibraries(asFileName(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        Container::loadDefaultProviders("Test")->unwrap($error);
        $this->assertNull($error);
        anyError(function() {
            yield Container::run($this->makeSureInputWorks(...));
        })->unwrap($error);
        $this->assertNull($error);
    }


    private function makeSureInputWorks(DocumentInterface $compiler):void {
        $text = $compiler->run(asFileName(__DIR__, './Assets/HelloWorld.php'), ['username' => "world"])->unwrap($error);
        $this->assertNull($error);
        $this->assertEquals('<span>hello world</span>', trim($text));
    }
}