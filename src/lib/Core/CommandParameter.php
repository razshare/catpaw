<?php
namespace CatPaw\Core;

readonly class CommandParameter {
    /**
     * @param  string $longName
     * @param  string $shortName
     * @param  bool   $required
     * @param  string $value
     * @return void
     */
    public function __construct(
        public string $longName,
        public string $shortName,
        public bool $required,
        public bool $updated,
        public string $value,
    ) {
    }
}