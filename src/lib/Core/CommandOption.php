<?php
namespace CatPaw\Core;

class CommandOption {
    /**
     * @param  string         $longName
     * @param  string         $shortName
     * @param  bool           $isFlag
     * @param  Result<string> $value
     * @return self
     */
    public static function create(
        string $longName,
        string $shortName,
        bool $isFlag,
        Result $value,
    ) {
        return new self(
            longName: $longName,
            shortName: $shortName,
            isFlag: $isFlag,
            value: $value,
        );
    }

    /**
     * @param  string         $longName
     * @param  string         $shortName
     * @param  bool           $isFlag
     * @param  Result<string> $value
     * @return void
     */
    private function __construct(
        public readonly string $longName,
        public readonly string $shortName,
        public readonly bool $isFlag,
        public Result $value,
    ) {
    }
}