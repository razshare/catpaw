<?php
namespace CatPaw\Core;

class CommandOption {
    /**
     * @param  string         $longName
     * @param  string         $shortName
     * @param  Result<string> $valueResult
     * @return void
     */
    public function __construct(
        public readonly string $longName,
        public readonly string $shortName,
        public Result $valueResult,
    ) {
    }
}