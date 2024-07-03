<?php
namespace CatPaw\Core;

class CommandOption {
    /**
     * @param  string         $longName
     * @param  string         $shortName
     * @param  bool           $isFlag
     * @param  Unsafe<string> $value
     * @return void
     */
    public function __construct(
        public readonly string $longName,
        public readonly string $shortName,
        public readonly bool $isFlag,
        public Unsafe $value,
    ) {
    }
}