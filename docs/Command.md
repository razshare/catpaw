# Command

You can register console commands using `$command->register()`

```php
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;

$runner = new class implements CommandRunnerInterface {
    /**
     * Build the command.
     * @param  CommandBuilder $builder
     * @return Result<None>
     */
    public function build(CommandBuilder $builder): void{
        $builder->withOption('o','--option', error('No value provided.'));
        $builder->requires('o');
    }

    /**
     * Run the command.
     * @return Result<None>
     */
    public function run(CommandContext $context): Result{
        $value = $context->get('o')->unwrap($error);
        if($error){
            return error($error);
        }
        return ok();
    }
}

function main(CommandInterface $command){
    return $command->register($runner);
}
```

Your command will the execute whenever the console user issues the required options of your command.