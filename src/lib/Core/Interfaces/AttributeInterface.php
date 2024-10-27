<?php

namespace CatPaw\Core\Interfaces;

use CatPaw\Core\Result;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

interface AttributeInterface {
    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Result<self|false>
     */
    public static function findByParameter(ReflectionParameter $reflectionParameter):Result;

    /**
     * @param  ReflectionFunction  $reflectionFunction
     * @return Result<array<self>>
     */
    public static function findAllByFunction(ReflectionFunction $reflectionFunction):Result;

    /**
     * @param  ReflectionFunction $reflectionFunction
     * @return Result<false|self>
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction):Result;

    /**
     * @param  ReflectionMethod   $reflectionMethod
     * @return Result<false|self>
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod):Result;

    /**
     * @param  ReflectionClass    $reflectionClass
     * @return Result<false|self>
     */
    // @phpstan-ignore-next-line
    public static function findByClass(ReflectionClass $reflectionClass):Result;

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @return Result<false|self>
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty):Result;
}
