<?php

namespace CatPaw\Core\Interfaces;

use CatPaw\Core\Unsafe;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

interface AttributeInterface {
    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Unsafe<self|false>
     */
    public static function findByParameter(ReflectionParameter $reflectionParameter):Unsafe;

    /**
     * @param  ReflectionFunction  $reflectionFunction
     * @return Unsafe<array<self>>
     */
    public static function findAllByFunction(ReflectionFunction $reflectionFunction):Unsafe;

    /**
     * @param  ReflectionFunction $reflectionFunction
     * @return Unsafe<false|self>
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction):Unsafe;

    /**
     * @param  ReflectionMethod   $reflectionMethod
     * @return Unsafe<false|self>
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod):Unsafe;

    /**
     * @param  ReflectionClass    $reflectionClass
     * @return Unsafe<false|self>
     */
    // @phpstan-ignore-next-line
    public static function findByClass(ReflectionClass $reflectionClass):Unsafe;

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @return Unsafe<false|self>
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty):Unsafe;
}
