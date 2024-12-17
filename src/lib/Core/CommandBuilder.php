<?php
namespace CatPaw\Core;

define('ZERO_RESULT', ok('0'));

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
     * @param  Result<string> $fallback
     * @return CommandBuilder
     */
    public function withOption(string $shortName, string $longName, Result $fallback = ZERO_RESULT):self {
        $option = new CommandOption(
            longName: $longName,
            shortName: $shortName,
            valueResult: $fallback,
        );
        $this->options[$longName]  = $option;
        $this->options[$shortName] = $option;
        return $this;
    }
}