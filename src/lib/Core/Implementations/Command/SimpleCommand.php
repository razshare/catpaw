<?php
namespace CatPaw\Core\Implementations\Command;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\CommandOption;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\CommandInterface as InterfacesCommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use Error;
use Throwable;

class NoMatchError extends Error {
    public function __construct(string $message, int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    public function __toString() {
        return __CLASS__.": [{$this->code}]: {$this->message}\n";
    }
}

class SimpleCommand implements InterfacesCommandInterface {
    /**
     * 
     * @param  CommandRunnerInterface $command
     * @return Result<None>
     */
    public function register(CommandRunnerInterface $command):Result {
        global $argv;
        
        $builder = new CommandBuilder;
        $command->build($builder);

        /** @var array<string,CommandOption> */
        $map = [];
        foreach ($builder->options() as $option) {
            if (!isset($map[$option->longName])) {
                $map[$option->longName] = $option;
            }

            if ('' !== $option->shortName) {
                if ($len = strlen($option->shortName) > 1) {
                    return error("Short names must be exactly 1 character long, received `{$option->shortName}`, which is $len characters long.");
                }
                if (!isset($map[$option->shortName])) {
                    $map[$option->shortName] = $option;
                }
            }
        }

        parse_str(implode('&', array_slice($argv, 1)), $inputs);

        /** @var array<string,mixed> $inputs */
        /** @var array<string,CommandOption> $options */
        $options = [];
        foreach ($map as $name => $option) {
            $value    = $option->valueResult->unwrap($error);
            $required = null !== $error;

            if ($required) {
                $value = match (true) {
                    isset($inputs["--$option->longName"]) => $inputs["--$option->longName"] ?? '',
                    isset($inputs["-$option->shortName"]) => $inputs["-$option->shortName"] ?? '',
                    default                               => false,
                };

                if (false === $value) {
                    return error(new NoMatchError('No match.'));
                }
            }

            $options[$name] = new CommandOption(
                longName: $option->longName,
                shortName: $option->shortName,
                valueResult: ok($value),
            );
        }

        $commandContext = new CommandContext($options);

        return $command->run($commandContext);
    }
}
