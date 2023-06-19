<?php

namespace CatPaw\Attributes\Interfaces;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

interface AttributeInterface {
    public static function findByFunction(ReflectionFunction $reflectionFunction);

    public static function findByMethod(ReflectionMethod $reflectionMethod);

    public static function findByClass(ReflectionClass $reflectionClass);

    public static function findByProperty(ReflectionProperty $reflectionProperty);

    /**
     * Invoked when this attribute is detected on a parameter.
     * @param  ReflectionParameter $reflection reflection of the parameter.
     * @param  mixed               $value      current value of the parameter.
     * @param  mixed               $context    context if available, false otherwise.
     * @return void
     */
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, mixed $context);


    /**
     * Invoked when this attribute is detected on a route handler.
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/9.Filters.md
     * @param  ReflectionFunction $reflection reflection of the function.
     * @param  Closure            $value      function to which this attribute is attatched to.
     * @param  mixed              $context    context if available, false otherwise.
     * @return void
     */
    public function onRouteMount(ReflectionFunction $reflection, Closure &$value, mixed $context);


    /**
     * Invoked when this attribute is detected on a class.
     * @param  ReflectionClass $reflection reflection of the class.
     * @param  mixed           $value      instance of the class.
     * @param  mixed           $context    context if available, false otherwise.
     * @return void
     */
    public function onClassMount(ReflectionClass $reflection, mixed &$value, mixed $context);
}