<?php
namespace CatPaw\Core;

class CommandContext {
    /**
     * 
     * @param  array<string,CommandOption> $options
     * @return void
     */
    public function __construct(private array $options) {
    }

    /**
     * Get a command option.\
     * Flags are excluded. Use `isset()` to check for flags.
     * @param  string         $name Name of the option, short or long.
     * @return Result<string>
     */
    public function get(string $name):Result {
        if (!isset($this->options[$name])) {
            return error("Command option `$name` not found.");
        }

        $value = $this->options[$name]->valueResult->unwrap($error);
        if ($error) {
            return error($error);
        }
        return ok($value);
    }
}