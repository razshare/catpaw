<?php
namespace CatPaw\Core;

readonly class CommandContext {
    /**
     * 
     * @param  array<string,CommandOption> $options
     * @return void
     */
    public function __construct(
        private array $options,
    ) {
    }

    /**
     * Get a command option.\
     * Flags are excluded. Use `isset()` to check for flags.
     * @param  string         $name Name of the option, short or long.
     * @return Unsafe<string>
     */
    public function get(string $name):Unsafe {
        if (!isset($this->options[$name])) {
            return error("Command option `$name` not found.");
        }

        $value = $this->options[$name]->value->unwrap($error);
        if ($error) {
            return error($error);
        }
        return ok($value);
    }
}