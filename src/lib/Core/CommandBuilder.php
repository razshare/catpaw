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
     * @param  Result<string> $value
     * @return CommandBuilder
     */
    public function withOption(string $shortName, string $longName, false|Result $value = false):self {
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
     * Add required command option.
     * @param  string         $shortName
     * @param  string         $longName
     * @return CommandBuilder
     */
    public function withRequiredOption(string $shortName, string $longName):self {
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