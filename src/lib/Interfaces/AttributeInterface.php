<?php

namespace CatPaw\Interfaces;

use CatPaw\DependenciesOptions;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

interface AttributeInterface {
    public static function findAllByFunction(ReflectionFunction $reflectionFunction): array|false ;
    
    public static function findByFunction(ReflectionFunction $reflectionFunction): self|false;

    public static function findByMethod(ReflectionMethod $reflectionMethod): self|false;

    public static function findByClass(ReflectionClass $reflectionClass): self|false;

    public static function findByProperty(ReflectionProperty $reflectionProperty): self|false;

    /**
     * Invoked when this attribute is detected on a parameter.
     * @param  ReflectionParameter $reflection reflection of the parameter.
     * @param  mixed               $value      current value of the parameter.
     * @param  DependenciesOptions $options    options used to find dependencies.
     * @return void
     */
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):void;


    /**
     * Invoked when this attribute is detected on a function.
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/9.Filters.md
     * @param  ReflectionFunction  $reflection reflection of the function.
     * @param  Closure             $value      function to which this attribute is attatched to.
     * @param  DependenciesOptions $options    options used to find dependencies.
     * @return void
     */
    public function onFunctionMount(ReflectionFunction $reflection, Closure &$value, DependenciesOptions $options):void;

    /**
     * Invoked when this attribute is detected on a class.
     * @param  ReflectionClass     $reflection reflection of the class.
     * @param  mixed               $value      instance of the class.
     * @param  DependenciesOptions $options    options used to find dependencies.
     * @return void
     */
    public function onClassMount(ReflectionClass $reflection, mixed &$value, DependenciesOptions $options):void;
}