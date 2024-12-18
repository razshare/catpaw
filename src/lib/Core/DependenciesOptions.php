<?php
namespace CatPaw\Core;

class DependenciesOptions {
    /**
     * @param string                                                      $key,
     * @param array<callable(DependencySearchResultItem):(Result<mixed>)> $overwrites,
     * @param array<callable(DependencySearchResultItem):(Result<mixed>)> $provides,
     * @param array<callable(DependencySearchResultItem):(Result<mixed>)> $fallbacks,
     * @param array<mixed>                                                $defaultArguments,
     *                                                                                       $defaultArguments,
     * @param mixed                                                       $context,
     */
    public function __construct(
        public string $key,
        public array $overwrites,
        public array $provides,
        public array $fallbacks,
        public array $defaultArguments,
        public mixed $context,
    ) {
    }
}
