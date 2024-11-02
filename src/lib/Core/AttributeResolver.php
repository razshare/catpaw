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
    public static function classAttributeArguments(ReflectionClass $reflectionClass, string $attributeName):false|array {
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
    public static function classAttribute(ReflectionClass $reflectionClass, string $attributeName):false|string {
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
    public static function functionAllAttributesArguments(ReflectionFunction $reflection_function, string $attributeName):false|array {
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
    public static function functionAttributeArguments(ReflectionFunction $reflection_function, string $attributeName):false|array {
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
    public static function functionAttributes(ReflectionFunction $reflectionFunction, string $attributeName):false|array {
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
    public static function functionAttribute(ReflectionFunction $reflectionFunction, string $attributeName):false|string {
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
    public static function methodAttributeArguments(ReflectionMethod $reflectionMethod, string $attributeName):false|array {
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
    public static function methodAttribute(ReflectionMethod $reflectionMethod, string $attributeName):false|string {
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
    public static function propertyAttributeArguments(ReflectionProperty $reflectionProperty, string $attributeName):false|array {
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
    public static function propertyAttribute(ReflectionProperty $reflectionProperty, string $attributeName):false|string {
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
    public static function parameterAttributeArguments(ReflectionParameter $reflectionParameter, string $attributeName):false|array {
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
    public static function parameterAllAttributeArguments(ReflectionParameter $reflectionParameter, string $attributeName):false|array {
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
    public static function parameterAttributes(ReflectionParameter $reflectionParameter, string $attributeName):false|array {
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
    public static function parameterAttribute(ReflectionParameter $reflectionParameter, string $attributeName):false|string {
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
