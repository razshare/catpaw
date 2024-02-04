<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use function CatPaw\Core\env;
use CatPaw\Core\Services\EnvironmentService;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase {
    public function testAll() {
        Container::load('./src/lib/')->try($error);
        $this->assertFalse($error);
        anyError(function() {
            yield Container::run($this->makeSureEnvWorks(...));
        })->try($error);
        $this->assertFalse($error);
    }


    private function makeSureEnvWorks(EnvironmentService $service): void {
        $service->setFileName(asFileName(__DIR__, 'env.yaml'));
        $service->load()->try($error);
        $this->assertFalse($error);
        $message = env("say.hello");
        $this->assertEquals('hello world', $message);
    }
}
