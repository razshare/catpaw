<?php
namespace CatPaw\Core;

class DependenciesOptions {
    /**
     * @param string          $key,
     * @param array<callable> $overwrites,
     * @param array<callable> $provides,
     * @param array<callable> $fallbacks,
     * @param array<mixed>    $defaultArguments,
     * @param mixed           $context,
     */
    public static function create(
        string $key,
        array $overwrites,
        array $provides,
        array $fallbacks,
        array $defaultArguments,
        mixed $context,
    ):self {
        return new self(
            key: $key,
            overwrites: $overwrites,
            provides: $provides,
            fallbacks: $fallbacks,
            defaultArguments: $defaultArguments,
            context: $context,
        );
    }

    /**
     * @param string          $key,
     * @param array<callable> $overwrites,
     * @param array<callable> $provides,
     * @param array<callable> $fallbacks,
     * @param array<mixed>    $defaultArguments,
     *                                           $defaultArguments,
     * @param mixed           $context,
     */
    private function __construct(
        public string $key,
        public array $overwrites,
        public array $provides,
        public array $fallbacks,
        public array $defaultArguments,
        public mixed $context,
    ) {
    }
}
