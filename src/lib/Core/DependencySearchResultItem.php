<?php
namespace CatPaw\Core;

use ReflectionAttribute;
use ReflectionParameter;

class DependencySearchResultItem {
    /**
     * @param ReflectionParameter                $reflectionParameter
     * @param mixed                              $defaultArgument
     * @param bool                               $isOptional
     * @param mixed                              $defaultValue
     * @param string                             $type
     * @param string                             $name
     * @param array<ReflectionAttribute<object>> $attributes
     */
    public function __construct(
        public ReflectionParameter $reflectionParameter,
        public mixed $defaultArgument,
        public bool $isOptional,
        public mixed $defaultValue,
        public string $type,
        public string $name,
        public array $attributes,
    ) {
    }
}
