# Command

You can register console commands using `$command->register()`

```php
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;

$runner = new class implements CommandRunnerInterface {
    /**
     * Build the command.
     * @param  CommandBuilder $builder
     * @return Result<None>
     */
    public function build(CommandBuilder $builder):Result{
        // ...
    }

    /**
     * Run the command.
     * @return Result<None>
     */
    public function run(CommandContext $context):Result{
        // ...
    }
}

function main(CommandInterface $command){
    return $command->register($runner);
}
```

Your command will the execute whenever the console user issues the required flags of your command.

> [!NOTE]
> Generally you should always define at least one unique flag using `$builder->withFlag()` or one unique tag using `$builder->withTag()` in order to avoid ambiguity.