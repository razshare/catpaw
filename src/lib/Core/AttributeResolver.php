<?php

namespace CatPaw\Core;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

class AttributeResolver {
    /**
     * @param  ReflectionClass<object>                  $reflectionClass
     * @param  string                                   $attributeName
     * @return false|array<ReflectionAttribute<object>>
     */
    public static function getClassAttributeArguments(ReflectionClass $reflectionClass, string $attributeName): false|array {
        $attributes = $reflectionClass->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return false;
    }

    /**
     * @param  ReflectionClass<object> $reflectionClass
     * @param  string                  $attributeName
     * @return false|string
     */
    public static function issetClassAttribute(ReflectionClass $reflectionClass, string $attributeName): false|string {
        $attributes = $reflectionClass->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }

    /**
     * @param  ReflectionFunction $reflection_function
     * @param  string             $attributeName
     * @return false|array<mixed>
     */
    public static function getFunctionAllAttributesArguments(ReflectionFunction $reflection_function, string $attributeName): false|array {
        $arguments  = [];
        $attributes = $reflection_function->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                $arguments[] = $attribute->getArguments();
            }
        }
        return $arguments;
    }

    /**
     * @param  ReflectionFunction $reflection_function
     * @param  string             $attributeName
     * @return false|array<mixed>
     */
    public static function getFunctionAttributeArguments(ReflectionFunction $reflection_function, string $attributeName): false|array {
        $attributes = $reflection_function->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return false;
    }

    /**
     * @param  ReflectionFunction  $reflectionFunction
     * @param  string              $attributeName
     * @return false|array<string>
     */
    public static function issetFunctionAttributes(ReflectionFunction $reflectionFunction, string $attributeName): false|array {
        $attributes = $reflectionFunction->getAttributes();
        $result     = [];
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                $result[] = $className;
            }
        }

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * @param  ReflectionFunction $reflectionFunction
     * @param  string             $attributeName
     * @return false|string
     */
    public static function issetFunctionAttribute(ReflectionFunction $reflectionFunction, string $attributeName): false|string {
        $attributes = $reflectionFunction->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }

    /**
     * @param  ReflectionMethod   $reflectionMethod
     * @param  string             $attributeName
     * @return false|array<mixed>
     */
    public static function getMethodAttributeArguments(ReflectionMethod $reflectionMethod, string $attributeName): false|array {
        $attributes = $reflectionMethod->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return false;
    }

    /**
     * @param  ReflectionMethod $reflectionMethod
     * @param  string           $attributeName
     * @return false|string
     */
    public static function issetMethodAttribute(ReflectionMethod $reflectionMethod, string $attributeName): false|string {
        $attributes = $reflectionMethod->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @param  string             $attributeName
     * @return false|array<mixed>
     */
    public static function getPropertyAttributeArguments(ReflectionProperty $reflectionProperty, string $attributeName): false|array {
        $attributes = $reflectionProperty->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return false;
    }

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @param  string             $attributeName
     * @return false|string
     */
    public static function issetPropertyAttribute(ReflectionProperty $reflectionProperty, string $attributeName): false|string {
        $attributes = $reflectionProperty->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @param  string              $attributeName
     * @return false|array<mixed>
     */
    public static function getParameterAttributeArguments(ReflectionParameter $reflectionParameter, string $attributeName): false|array {
        $attributes = $reflectionParameter->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $attribute->getArguments();
            }
        }
        return false;
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @param  string              $attributeName
     * @return false|array<mixed>
     */
    public static function getParameterAllAttributeArguments(ReflectionParameter $reflectionParameter, string $attributeName): false|array {
        $arguments  = [];
        $attributes = $reflectionParameter->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                $arguments[] = $attribute->getArguments();
            }
        }
        return $arguments;
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @param  string              $attributeName
     * @return false|array<string>
     */
    public static function issetParameterAttributes(ReflectionParameter $reflectionParameter, string $attributeName): false|array {
        $attributes = $reflectionParameter->getAttributes();
        $result     = [];
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                $result[] = $className;
            }
        }

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @param  string              $attributeName
     * @return false|string
     */
    public static function issetParameterAttribute(ReflectionParameter $reflectionParameter, string $attributeName): false|string {
        $attributes = $reflectionParameter->getAttributes();
        foreach ($attributes as $attribute) {
            $className = $attribute->getName();
            // @phpstan-ignore-next-line
            if ($className === $attributeName || is_subclass_of($className, $attributeName)) {
                return $className;
            }
        }
        return false;
    }
}
