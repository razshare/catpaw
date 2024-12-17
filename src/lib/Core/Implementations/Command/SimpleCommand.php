<?php
namespace CatPaw\Core\Implementations\Command;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\CommandParameter;
use function CatPaw\Core\error;

use CatPaw\Core\Interfaces\CommandInterface as InterfacesCommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use CatPaw\Core\None;
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
        $parameters = &$builder->parameters();
        parse_str(implode('&', array_slice($argv, 1)), $inputs);

        foreach ($inputs as $key => $value) {
            /** @var false|int */
            $index = false;

            foreach ($parameters as $indexLocal => $parameterLocal) {
                $longNameDashed  = "--$parameterLocal->longName";
                $shortNameDashed = "-$parameterLocal->shortName";
                if ($key === $longNameDashed || $key === $shortNameDashed) {
                    $index = $indexLocal;
                    break;
                }
            }

            if (false === $index) {
                continue;
            }

            $value = preg_replace('/(?<!\\\\)"/', '', $value);
            $value = stripcslashes($value);
            if('' === $value){
                $value = '1';
            }

            $parameters[$index] = new CommandParameter(
                longName: $parameters[$index]->longName,
                shortName: $parameters[$index]->shortName,
                required: $parameters[$index]->required,
                updated: true,
                value: $value,
            );
        }

        foreach ($parameters as $indexLocal => $parameter) {
            if ($parameter->required && !$parameter->updated) {
                return error(new NoMatchError("Command parameter `{$parameter->longName}` is required."));
            }
        }

        $commandContext = new CommandContext($parameters);

        return $command->run($commandContext);
    }
}
