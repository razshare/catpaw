<?php
namespace CatPaw\Core;

class CommandContext {
    /**
     * 
     * @param  array<string,CommandOption> $options
     * @return self
     */
    public static function create(array $options) {
        return new self($options);
    }

    /**
     * 
     * @param  array<string,CommandOption> $options
     * @return void
     */
    private function __construct(private array $options) {
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

        $value = $this->options[$name]->value->unwrap($error);
        if ($error) {
            return error($error);
        }
        return ok($value);
    }
}