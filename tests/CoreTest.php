<?php
namespace Tests;

use function CatPaw\Core\anyError;
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\Container;
use function CatPaw\Core\env;
use CatPaw\Core\File;
use CatPaw\Core\FileName;
use CatPaw\Core\Implementations\CommandRegister\SimpleCommandRegister;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRegisterInterface;
use CatPaw\Core\Interfaces\EnvironmentInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Core\Signal;
use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase {
    public function testAll():void {
        Container::provide(CommandRegisterInterface::class, new SimpleCommandRegister);
        Container::requireLibraries(FileName::create(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        Container::loadDefaultProviders("Test")->unwrap($error);
        $this->assertNull($error);
        Container::run($this->makeSureEnvWorks(...))->unwrap($error);
        $this->assertNull($error);
        Container::run($this->makeSureUnsafeWorks(...))->unwrap($error);
        $this->assertNull($error);
        Container::run($this->makeSureUnsafeWorksWithAnyError(...))->unwrap($error);
        $this->assertNull($error);
        Container::run($this->makeSureSignalsWork(...))->unwrap($error);
        $this->assertNull($error);
        Container::run($this->makeSureCommandWorks(...))->unwrap($error);
        $this->assertNull($error);
    }


    private function makeSureEnvWorks(EnvironmentInterface $environment):void {
        $environment->withFileName(FileName::create(__DIR__, 'env.ini'));
        $environment->load()->unwrap($error);
        $this->assertNull($error);
        $sayHello = env("say.hello");
        $this->assertEquals('hello world', $sayHello);
        $environment->set("test-key", "test-value");
        $test = env("test-key");
        $this->assertEquals('test-value', $test);
    }

    public function makeSureUnsafeWorks():void {
        $file = File::open(FileName::create(__DIR__, 'file.txt'))->unwrap($error);
        $this->assertNull($error);
        $contents = $file->readAll()->unwrap($error);
        $this->assertNull($error);
        $this->assertEquals("hello\n", $contents);
        $file->close();
        echo $contents.PHP_EOL;
    }

    public function makeSureUnsafeWorksWithAnyError():void {
        $file = File::open(FileName::create(__DIR__, 'file.txt'))->unwrap($error);
        $this->assertNull($error);
        $contents = $file->readAll()->unwrap($error);
        $this->assertNull($error);
        $file->close();
        $this->assertNull($error);
        $this->assertEquals("hello\n", $contents);
        echo $contents.PHP_EOL;
    }

    public function makeSureSignalsWork():void {
        $signal  = new Signal();
        $counter = 0;
        $signal->listen(function() use (&$counter) {
            $counter++;
        });
        $signal->send();
        $this->assertEquals(1, $counter);
    }

    /**
     * @return Result<None>
     */
    public function makeSureCommandWorks(CommandRegisterInterface $command):Result {
        return anyError(function() use ($command) {
            global $argv;

            $argv[] = '--run';
            $argv[] = '--port="\\"80"';
            $argv[] = '--certificate="some-certificate.txt"';
            
            $command->register($runner = new class implements CommandInterface {
                public CommandBuilder $builder;
                public function build(CommandBuilder $builder):void {
                    $this->builder = $builder;
                    $builder->required('r', 'run');
                    $builder->optional('p', 'port');
                    $builder->optional('c', 'certificate');
                }
            
                public function run(CommandContext $context):Result {
                    $context->get('run');
                    return ok();
                }
            })->unwrap($error);
            
            $parameters = $runner->builder->parameters();

            $this->assertArrayHasKey(0, $parameters);
            $this->assertArrayHasKey(1, $parameters);
            $this->assertArrayHasKey(2, $parameters);

            $parameterRun         = $parameters[0];
            $parameterPort        = $parameters[1];
            $parameterCertificate = $parameters[2];

            $this->assertEquals('run', $parameterRun->longName);
            $this->assertEquals('port', $parameterPort->longName);
            $this->assertEquals('certificate', $parameterCertificate->longName);

            $this->assertEquals('r', $parameterRun->shortName);
            $this->assertEquals('p', $parameterPort->shortName);
            $this->assertEquals('c', $parameterCertificate->shortName);

            $this->assertEquals('1', $parameterRun->value);
            $this->assertEquals('"80', $parameterPort->value);
            $this->assertEquals('some-certificate.txt', $parameterCertificate->value);

            return ok();
        });
    }
}

interface Contract {
    public function hello(string $name):string;
}