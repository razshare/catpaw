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


    /** @var array<string> */
    private array $required = [];

    /**
     * Get the required options.
     * @return array<string>
     */
    public function required():array {
        return $this->required;
    }

    /**
     * Enforce an option to be required.
     * @param  string $name
     * @return void
     */
    public function requires(string $name):void {
        $this->required[$name] = true;
    }

    /**
     * Add a command option.
     * @param  string         $shortName
     * @param  string         $longName
     * @param  Result<string> $value
     * @return CommandBuilder
     */
    public function withOption(string $shortName, string $longName, Result $value):self {
        $option = CommandOption::create(
            longName: $longName,
            shortName: $shortName,
            value: $value,
        );
        $this->options[$longName]  = $option;
        $this->options[$shortName] = $option;
        return $this;
    }
}