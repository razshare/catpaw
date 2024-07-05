# Command

You can create and run console commands using `CommandInterface::register()`.



```php
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;

function main(CommandInterface $command) {
    return $command->register(new class implements CommandRunnerInterface {
        // ...
    });
}
```

You will need to implement `CommandRunnerInterface`

```php
interface CommandRunnerInterface {
    /**
     * Build the command.
     * @param  CommandBuilder $builder
     * @return Unsafe<None>
     */
    public function build(CommandBuilder $builder):Unsafe;

    /**
     * Run the command.
     * @return Unsafe<None>
     */
    public function run(CommandContext $context):Unsafe;
}
```

The two methods have 2 different life cycles.

The `build()` method executes immediately after invoking `CommandInterface::register()`.
Inside `build()` you can attach options and flags to your command using `$builder->withRequiredFlag()` for defining a required flag
```php
/**
 * Build the command.
 * @param  CommandBuilder $builder
 * @return Unsafe<None>
 */
public function build(CommandBuilder $builder):Unsafe {
    $builder->withRequiredFlag('h','help');
    return ok();
}
```
or `$builder->withFlag()` for defining an optional flag

```php
/**
 * Build the command.
 * @param  CommandBuilder $builder
 * @return Unsafe<None>
 */
public function build(CommandBuilder $builder):Unsafe {
    $builder->withFlag('h','help');
    return ok();
}
```

> [!NOTE]
> Flags are just options that don't accept any value.

or `$builder->withOption()` for defining options

```php
/**
 * Build the command.
 * @param  CommandBuilder $builder
 * @return Unsafe<None>
 */
public function build(CommandBuilder $builder):Unsafe {
    $builder->withOption('n','name', error('Name options is required.'));
    return ok();
}
```
Your command will the execute whenever the console user issues the required flags of your command.

> [!NOTE]
> Generally you should always define at least one unique required flag using `$builder->withRequiredFlag()` in order to avoid ambiguity.

The second method, `run()`, is where you can write your logic.\
This method is only executed if the console user issues a request to your command, meaning - they've invoked the program with all the required flags.

So for example, if your build method looks like this

```php
/**
 * Build the command.
 * @param  CommandBuilder $builder
 * @return Unsafe<None>
 */
public function build(CommandBuilder $builder):Unsafe {
    $builder->withRequiredFlag('s','start');
    $builder->withOption('p','port', ok('80'));
    return ok();
}
```

Then, in order to execute the command, the console user should invoke the program using the `start` flag

```bash
php app.phar --start
```

and optionally they can specify a `port`

```bash
php app.phar --start --port="80"
```

## Build

The `build()` method takes a `CommandBuilder`, which, as mentioned above, you can use to attach options and flags to your command.

```php
use function CatPaw\Core\ok;
use CatPaw\Core\None;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;

function main(CommandInterface $command) {
    return $command->register(new class implements CommandRunnerInterface {
        /**
         * Build the command.
         * @param  CommandBuilder $builder
         * @return Unsafe<None>
         */
        public function build(CommandBuilder $builder):Unsafe {
            $builder->withRequiredFlag('h','help');
            return ok();
        }

        /**
         * Run the command.
         * @return Unsafe<None>
         */
        public function run(CommandContext $context):Unsafe{
            echo "See https://www.php.net/ for more help.\n";
            return ok();
        }
    });
}
```

## Run

The `run()` method takes a `CommandContext`, which contains the options of your command.

> [!NOTE]
> In the future, `CommandContext` could possibly include more information than simply the options of the command.\
> For example it would be useful to know which the Operating System we're running on, the path to the Php binary, the path to the Go binary if available, etc.

```php
use function CatPaw\Core\ok;
use CatPaw\Core\None;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;

function main(CommandInterface $command) {
    return $command->register(new class implements CommandRunnerInterface {
        /**
         * Build the command.
         * @param  CommandBuilder $builder
         * @return Unsafe<None>
         */
        public function build(CommandBuilder $builder):Unsafe {
            $builder->withRequiredFlag('g','greeting');
            $builder->withOption('n','name', ok('world'));
            return ok();
        }

        /**
         * Run the command.
         * @return Unsafe<None>
         */
        public function run(CommandContext $context):Unsafe{
            $name = $context->get('name')->try();
            // you can also find the option by its short name, like so:
            // $name = $context->get('n')->try();
            echo "hello $name\n";
            return ok();
        }
    });
}
```

## Flags

Flags are option that don't accept any value.
Any flag is automatically coerced into `'1'` when the user issues the flag, or `'0'` when the user doesn't issue the flag.

Casting flags to `bool` is pretty straight forward.

```php
use function CatPaw\Core\ok;
use CatPaw\Core\None;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;

function main(CommandInterface $command) {
    return $command->register(new class implements CommandRunnerInterface {
        /**
         * Build the command.
         * @param  CommandBuilder $builder
         * @return Unsafe<None>
         */
        public function build(CommandBuilder $builder):Unsafe {
            $builder->withRequiredFlag('h','hello');
            $builder->withFlag('n','be-nice');
            return ok();
        }

        /**
         * Run the command.
         * @return Unsafe<None>
         */
        public function run(CommandContext $context):Unsafe{
            $beNice = (bool)$context->get('n')->try();  // Casting to bool here.
            
            if($beNice){
                echo "Hello!\n";
            } else {
                echo "Dogs drool, cats rule!\n";
            }

            return ok();
        }
    });
}
```