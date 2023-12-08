<?php
namespace CatPaw;

class DependenciesOptions {
    /**
     * @param array<string>   $ids,
     * @param array<callable> $provides,
     * @param array<callable> $fallbacks,
     * @param array<mixed>    $defaultArguments,
     * @param mixed           $context,
     */
    public function __construct(
        public array $ids,
        public array $provides,
        public array $fallbacks,
        public array $defaultArguments,
        public mixed $context,
    ) {
    }
}