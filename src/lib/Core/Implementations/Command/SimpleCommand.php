<?php
namespace CatPaw\Core\Implementations\Command;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\CommandOption;
use function CatPaw\Core\error;

use CatPaw\Core\Interfaces\CommandInterface as InterfacesCommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;

class SimpleCommand implements InterfacesCommandInterface {
    /**
     * 
     * @param  CommandRunnerInterface $command
     * @return Unsafe<bool>
     */
    public function register(CommandRunnerInterface $command):Unsafe {
        $builder = new CommandBuilder;
        $command->build($builder)->unwrap($error);
        if ($error) {
            return error($error);
        }
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

        $longOptions  = [];
        $shortOptions = "";

        foreach ($map as $key => $option) {
            if (strlen($key) <= 1) {
                continue;
            }

            $value = $option->value->unwrap($error);

            if ($option->isFlag) {
                // Not optional and doesn't accept a value.
                $longOptions[] = "{$option->longName}";
                $shortOptions .= "{$option->shortName}";
            } else if ($error) {
                // Not optional.
                $longOptions[] = "{$option->longName}:";
                $shortOptions .= "{$option->shortName}:";
            } else {
                if (!is_string($value)) {
                    $type = gettype($value);
                    return error("Command options can be either boolean or strings, received `$type` instead.");
                }
                // Optional and accepts a value.
                $longOptions[] = "{$option->longName}::";
                $shortOptions .= "{$option->shortName}::";
            }
        }

        $opts = getopt($shortOptions, $longOptions);

        $removableKeys = [];

        foreach ($opts as $key => $value) {
            if (is_array($value)) {
                $removableKeys[] = $key;
            }
        }

        foreach ($removableKeys as $key) {
            unset($opts[$key]);
        }

        $optsCount = count($opts);
        $mapCount  = count($map);

        if (0 === $optsCount && 0 !== $mapCount) {
            $optionals = 0;
            foreach ($longOptions as $longOption) {
                if (str_ends_with($longOption, '::')) {
                    $optionals++;
                }
            }
            $shortOptionsArray = array_filter(explode('::', $shortOptions), fn ($item) => '' !== $item);
            $shortOptionsCount = count($shortOptionsArray);
            $optionals += $shortOptionsCount;
            if ($mapCount - $optionals > 0) {
                return ok(false);
            }
        }

        foreach ($opts as $key => $value) {
            $option = $map[$key];
            if (false === $value) {
                $option->value = ok('1');
            } else {
                // @phpstan-ignore-next-line
                $option->value = ok($value);
            }
        }

        $command->run(CommandContext::create($map))->unwrap($error);
        if ($error) {
            return error($error);
        }
        return ok(true);
    }
}