<?php
namespace CatPaw\Core;

use ReflectionAttribute;
use ReflectionParameter;

class ContainerSearchResultItem {
    /**
     * @param ReflectionParameter                $reflectionParameter
     * @param bool                               $isOptional
     * @param mixed                              $defaultValue
     * @param string                             $type
     * @param string                             $name
     * @param array<ReflectionAttribute<object>> $attributes
     */
    public function __construct(
        public ReflectionParameter $reflectionParameter,
        public bool $isOptional,
        public mixed $defaultValue,
        public string $type,
        public string $name,
        public array $attributes,
    ) {
    }
}
