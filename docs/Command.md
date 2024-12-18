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
     * @return void
     */
    public function build(CommandBuilder $builder):void {
        $builder->required('o','option');
    }

    /**
     * Run the command.
     * @return Result<None>
     */
    public function run(CommandContext $context):Result {
        $value = $context->get('option');
        echo "$value\n";
        return ok();
    }
}

function main(CommandInterface $command){
    return $command->register($runner);
}
```

Your command will the execute whenever the console user issues the required parameters of your command.