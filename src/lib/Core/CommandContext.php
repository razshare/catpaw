<?php
namespace CatPaw\Core;

class CommandContext {
    /**
     * 
     * @param  array<int,CommandParameter> $parameters
     * @return void
     */
    public function __construct(private array $parameters) {
    }

    /**
     * Get a command parameter.
     * @param  string $name Name of the option, short or long.
     * @return string
     */
    public function get(string $name):string {
        foreach ($this->parameters as $parameter) {
            if ($name !== $parameter->longName && $name !== $parameter->shortName) {
                continue;
            }
            $value   = $parameter->value;
            $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN);

            if ('' !== $value && $boolean) {
                return '1';
            }

            return $value;
        }

        return '';
    }
}