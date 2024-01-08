<?php

namespace CatPaw;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

class AttributeResolver {
    public static function getClassAttributeArguments(ReflectionClass $reflectionClass, string $attributeName): ?array {
        $attributes = $reflectionClass->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return null;
    }

    public static function issetClassAttribute(ReflectionClass $reflectionClass, string $attributeName): false|string {
        $attributes = $reflectionClass->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }

    public static function getFunctionAllAttributesArguments(ReflectionFunction $reflection_function, string $attributeName): ?array {
        $arguments  = [];
        $attributes = $reflection_function->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                $arguments[] = $attribute->getArguments();
            }
        }
        return $arguments;
    }

    public static function getFunctionAttributeArguments(ReflectionFunction $reflection_function, string $attributeName): ?array {
        $attributes = $reflection_function->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return null;
    }

    public static function issetFunctionAttributes(ReflectionFunction $reflectionFunction, string $attributeName): false|array {
        $attributes = $reflectionFunction->getAttributes();
        $result     = [];
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                $result[] = $className;
            }
        }

        if ($result) {
            return $result;
        }

        return false;
    }

    public static function issetFunctionAttribute(ReflectionFunction $reflectionFunction, string $attributeName): false|string {
        $attributes = $reflectionFunction->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }

    public static function getMethodAttributeArguments(ReflectionMethod $reflectionMethod, string $attributeName): ?array {
        $attributes = $reflectionMethod->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return null;
    }

    public static function issetMethodAttribute(ReflectionMethod $reflectionMethod, string $attributeName): false|string {
        $attributes = $reflectionMethod->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }

    public static function getPropertyAttributeArguments(ReflectionProperty $reflectionProperty, string $attributeName): ?array {
        $attributes = $reflectionProperty->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return null;
    }

    public static function issetPropertyAttribute(ReflectionProperty $reflectionProperty, string $attributeName): false|string {
        $attributes = $reflectionProperty->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }

    public static function getParameterAttributeArguments(ReflectionParameter $reflectionParameter, string $attributeName): ?array {
        $attributes = $reflectionParameter->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return null;
    }

    public static function issetParameterAttribute(ReflectionParameter $reflectionParameter, string $attributeName): false|string {
        $attributes = $reflectionParameter->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }
}
