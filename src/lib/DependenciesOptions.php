<?php
namespace CatPaw;

class DependenciesOptions {
    /**
     * @param array<string>   $ids,
     * @param array<callable> $overwrites,
     * @param array<callable> $provides,
     * @param array<callable> $fallbacks,
     * @param array<mixed>    $defaultArguments,
     * @param mixed           $context,
     */
    public static function create(
        array $ids,
        array $overwrites,
        array $provides,
        array $fallbacks,
        array $defaultArguments,
        mixed $context,
    ):self {
        return new self(
            ids: $ids,
            overwrites: $overwrites,
            provides: $provides,
            fallbacks: $fallbacks,
            defaultArguments: $defaultArguments,
            context: $context,
        );
    }

    /**
     * @param array<string>   $ids,
     * @param array<callable> $overwrites,
     * @param array<callable> $provides,
     * @param array<callable> $fallbacks,
     * @param array<mixed>    $defaultArguments,
     * @param mixed           $context,
     */
    private function __construct(
        public array $ids,
        public array $overwrites,
        public array $provides,
        public array $fallbacks,
        public array $defaultArguments,
        public mixed $context,
    ) {
    }
}