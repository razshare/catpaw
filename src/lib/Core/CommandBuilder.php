<?php
namespace CatPaw\Core;

define('ZERO_RESULT', ok('0'));

class CommandBuilder {
    /** @var array<int,CommandParameter> */
    private array $parameters = [];

    /**
     * @return array<int,CommandParameter>
     */
    public function &parameters():array {
        return $this->parameters;
    }

    public function required(string $shortName, string $longName):void {
        $this->parameters[] = new CommandParameter(
            longName: $longName,
            shortName: $shortName,
            updated: false,
            required: true,
            value: '',
        );
    }

    public function optional(string $shortName, string $longName):void {
        $this->parameters[] = new CommandParameter(
            longName: $longName,
            shortName: $shortName,
            updated: false,
            required: false,
            value: '',
        );
    }
}