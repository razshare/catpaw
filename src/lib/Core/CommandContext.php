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
     * Get a command option.
     * @param  string         $name Name of the option, short or long.
     * @return string
     */
    public function get(string $name):string {
        if (!isset($this->options[$name]) || !$this->options[$name]) {
            return '0';
        }
        $value = $this->options[$name]->valueResult->unwrap($error);
        if ($error) {
            return '0';
        }

        if('' === $value){
            return '1';
        }

        return $value?:'0';
    }
}