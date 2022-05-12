<?php

namespace CatPaw\Attributes;

use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

class AttributeResolver{

    public static function getClassAttributeArguments(ReflectionClass $reflectionClass, string $attributeName):?array{
        $attributes = $reflectionClass->getAttributes();
        foreach($attributes as $attribute){
            $local_attribute_name = $attribute->getName();
            if($local_attribute_name === $attributeName)
                return $attribute->getArguments();
        }
        return null;
    }

    #[Pure] public static function issetClassAttribute(ReflectionClass $reflectionClass, string $attributeName):bool{
        $attributes = $reflectionClass->getAttributes();
        foreach($attributes as $attribute){
            $local_attribute_name = $attribute->getName();
            if($local_attribute_name === $attributeName)
                return true;
            
        }
        return false;
    }

    #[Pure] public static function getFunctionAttributeArguments(ReflectionFunction $reflection_function, string $attributeName):?array{
        $attributes = $reflection_function->getAttributes();
        foreach($attributes as $attribute){
            $classname = $attribute->getName();
            if($classname === $attributeName)
                return $attribute->getArguments();
        }
        return null;
    }

    #[Pure] public static function issetFunctionAttribute(ReflectionFunction $reflectionFunction, string $attributeName):bool{
        $attributes = $reflectionFunction->getAttributes();
        foreach($attributes as $attribute){
            $classname = $attribute->getName();
            if($classname === $attributeName)
                return true;
        }
        return false;
    }

    #[Pure] public static function getMethodAttributeArguments(ReflectionMethod $reflectionMethod, string $attributeName):?array{
        $attributes = $reflectionMethod->getAttributes();
        foreach($attributes as $attribute){
            $classname = $attribute->getName();
            if($classname === $attributeName)
                return $attribute->getArguments();
        }
        return null;
    }

    #[Pure] public static function issetMethodAttribute(ReflectionMethod $reflectionMethod, string $attributeName):bool{
        $attributes = $reflectionMethod->getAttributes();
        foreach($attributes as $attribute){
            $classname = $attribute->getName();
            if($classname === $attributeName)
                return true;
        }
        return false;
    }

    #[Pure] public static function getPropertyAttributeArguments(ReflectionProperty $reflectionProperty, string $attributeName):?array{
        $attributes = $reflectionProperty->getAttributes();
        foreach($attributes as $attribute){
            $classname = $attribute->getName();
            if($classname === $attributeName)
                return $attribute->getArguments();
        }
        return null;
    }

    #[Pure] public static function issetPropertyAttribute(ReflectionProperty $reflectionProperty, string $attributeName):bool{
        $attributes = $reflectionProperty->getAttributes();
        foreach($attributes as $attribute){
            $classname = $attribute->getName();
            if($classname === $attributeName)
                return true;
        }
        return false;
    }

    #[Pure] public static function getParameterAttributeArguments(ReflectionParameter $reflectionParameter, string $attributeName):?array{
        $attributes = $reflectionParameter->getAttributes();
        foreach($attributes as $attribute){
            $classname = $attribute->getName();
            if($classname === $attributeName)
                return $attribute->getArguments();
        }
        return null;
    }

    #[Pure] public static function issetParameterAttribute(ReflectionParameter $reflectionParameter, string $attributeName):bool{
        $attributes = $reflectionParameter->getAttributes();
        foreach($attributes as $attribute){
            $classname = $attribute->getName();
            if($classname === $attributeName)
                return true;
        }
        return false;
    }
}