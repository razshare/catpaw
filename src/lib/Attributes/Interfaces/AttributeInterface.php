<?php

namespace CatPaw\Attributes\Interfaces;

use Amp\Promise;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

interface AttributeInterface {
    public static function findByFunction(ReflectionFunction $reflectionMethod): Promise;

    public static function findByMethod(ReflectionMethod $reflectionMethod): Promise;

    public static function findByClass(ReflectionClass $reflectionClass): Promise;

    public static function findByProperty(ReflectionProperty $reflectionProperty): Promise;

    /**
     * Triggers whenever the attribute it assigned to a parameter.
     * @param  ReflectionParameter $reflection the reflection of the parameter.
     * @param  mixed               $value      the current value of the parameter.
     * @param  mixed               $context    the context if available, false otherwise.
     * @return Promise<void>
     */
    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context): Promise;


    /**
     * Triggers whenever the attribute is assigned to a route handler.<br/>
     * Route handlers are closure functions assigned using "Route::get", "Route::post", "Route::update", etc.<br/>
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/9.Filters.md
     * @param  ReflectionFunction $reflection
     * @param  Closure            $value      the function to which this attribute is attatched to.
     * @param  mixed              $context    the context if available, false otherwise.
     * @return Promise
     */
    public function onRouteHandler(ReflectionFunction $reflection, Closure &$value, mixed $context): Promise;


    /**
     * Triggers whenever a class is instantiated through dependency injection.
     * @param  ReflectionClass $reflection
     * @param  mixed           $value      the instance of the given class.
     * @param  mixed           $context    the context if available, false otherwise.
     * @return Promise
     */
    public function onClassInstantiation(ReflectionClass $reflection, mixed &$value, mixed $context): Promise;
}