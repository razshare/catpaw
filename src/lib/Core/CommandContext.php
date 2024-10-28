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

    private bool $accepted = false;

    /**
     * Accepts the command.
     * @return void 
     */
    public function accept(): void {
        $this->accepted = true;
    }

    /**
     * 
     * @return bool `true` if the command has been accepted, `false` otherwise.
     */
    public function accepted(): bool {
        return (bool)$this->accepted;
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