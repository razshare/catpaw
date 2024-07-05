<?php
namespace Tests;

use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\Container;
use function CatPaw\Core\env;
use CatPaw\Core\File;
use CatPaw\Core\GoffiContract;
use CatPaw\Core\Implementations\Command\SimpleCommand;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use CatPaw\Core\Interfaces\EnvironmentInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Signal;
use CatPaw\Core\Unsafe;

use PHPUnit\Framework\TestCase;

class CoreTest extends TestCase {
    public function testAll():void {
        Container::provide(CommandInterface::class, new SimpleCommand);
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
            yield Container::run($this->makeSureCommandWorks(...));
        })->unwrap($error);
        $this->assertNull($error);
    }


    private function makeSureEnvWorks(EnvironmentInterface $environment): void {
        $environment->withFileName(asFileName(__DIR__, 'env.ini'));
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
            $lib    = GoffiContract::create(Contract::class, asFileName(__DIR__, './main.so'))->try();
            $result = $lib->hello("world");
            $this->assertEquals("hello world", $result);
            return ok();
        });
    }

    /**
     * @return Unsafe<None>
     */
    public function makeSureCommandWorks(CommandInterface $command):Unsafe {
        return anyError(function() use ($command) {
            $command->register($runner = new class implements CommandRunnerInterface {
                public CommandBuilder $builder;
                public function build(CommandBuilder $builder): Unsafe {
                    $this->builder = $builder;
                    $builder->withRequiredFlag('r', 'run');
                    $builder->withOption('p', 'port', ok('80'));
                    $builder->withOption('c', 'certificate');
                    $builder->withFlag('s', 'secure');
                    return ok();
                }
            
                public function run(CommandContext $context): Unsafe {
                    return ok();
                }
            });

            $options = $runner->builder->options();

            $this->assertArrayHasKey('r', $options);
            $this->assertArrayHasKey('run', $options);
            $this->assertArrayHasKey('p', $options);
            $this->assertArrayHasKey('port', $options);
            $this->assertArrayHasKey('c', $options);
            $this->assertArrayHasKey('certificate', $options);
            $this->assertArrayHasKey('s', $options);
            $this->assertArrayHasKey('secure', $options);

            $r   = $options['r'];
            $run = $options['run'];
            $this->assertEquals($r, $run);

            $p    = $options['p'];
            $port = $options['port'];
            $this->assertEquals($p, $port);

            $c           = $options['c'];
            $certificate = $options['certificate'];
            $this->assertEquals($c, $certificate);

            $s      = $options['s'];
            $secure = $options['secure'];
            $this->assertEquals($s, $secure);

            $this->assertTrue($r->isFlag);
            $this->assertFalse($p->isFlag);
            $this->assertFalse($c->isFlag);
            $this->assertTrue($s->isFlag);

            $runValue = $run->value->unwrap($error);
            $this->assertNull($runValue);
            $this->assertEquals("Required flag `--run (-r)` is missing.", $error->getMessage());

            $portValue = $port->value->unwrap($error);
            $this->assertEquals('80', $portValue);

            $certificateValue = $certificate->value->unwrap($error);
            $this->assertEquals('', $certificateValue);
            
            $secureValue = $secure->value->unwrap($error);
            $this->assertEquals('0', $secureValue);

            return ok();
        });
    }
}

interface Contract {
    public function hello(string $name):string;
}
