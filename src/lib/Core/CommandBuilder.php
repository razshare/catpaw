<?php
namespace CatPaw\Core;

class CommandBuilder {
    /** @var array<string,CommandOption> */
    private array $options = [];

    /**
     * Get the command options.
     * @return array<string,CommandOption>
     */
    public function options():array {
        return $this->options;
    }

    /**
     * Add a command option.
     * @param  string         $shortName
     * @param  string         $longName
     * @param  Unsafe<string> $value
     * @return CommandBuilder
     */
    public function withOption(string $shortName, string $longName, false|Unsafe $value = false):self {
        $option = CommandOption::create(
            longName: $longName,
            shortName: $shortName,
            isFlag: false,
            value: $value?:ok(''),
        );
        $this->options[$longName]  = $option;
        $this->options[$shortName] = $option;
        return $this;
    }

    /**
     * Add a flag command option.\
     * Flags, as apposed to normal options, don't accept values and are meant to be used as booleans.
     * @param  string         $shortName
     * @param  string         $longName
     * @return CommandBuilder
     */
    public function withFlag(string $shortName, string $longName):self {
        $option = CommandOption::create(
            longName: $longName,
            shortName: $shortName,
            isFlag: true,
            value: ok('0'),
        );
        $this->options[$shortName] = $option;
        $this->options[$longName]  = $option;
        return $this;
    }

    /**
     * Add a required flag command option.\
     * Flags, as apposed to normal options, don't accept values and are meant to be used as booleans.\
     * If the console user doesn't issue this required flag, the command will not trigger under any circumstances.
     * @param  string         $shortName
     * @param  string         $longName
     * @return CommandBuilder
     */
    public function withRequiredFlag(string $shortName, string $longName):self {
        $option = CommandOption::create(
            longName: $longName,
            shortName: $shortName,
            isFlag: true,
            value: error("Required flag `--$longName (-$shortName)` is missing."),
        );
        $this->options[$shortName] = $option;
        $this->options[$longName]  = $option;
        return $this;
    }
}