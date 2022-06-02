<?php

namespace CatPaw\Attributes\Traits;

use function Amp\call;
use Amp\Promise;
use CatPaw\Attributes\AttributeResolver;
use CatPaw\Attributes\Entry;
use CatPaw\Utilities\Container;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

trait CoreAttributeDefinition {
    private static array $entry_cache = [];

    /**
     * @return array|false
     */
    private static function entry(): array|false {
        $i = new ReflectionClass(static::class);
        foreach ($i->getMethods() as $method) {
            if (($attributes = $method->getAttributes(Entry::class))) {
                if (count($attributes) > 0) {
                    return [$i, $method];
                }
            }
        }
        return [$i, false];
    }

    /**
     * @param  ReflectionFunction  $reflectionFunction
     * @return Promise<self|false>
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction): Promise {
        return call(function() use ($reflectionFunction) {
            if (!($trueClassName = AttributeResolver::issetFunctionAttribute($reflectionFunction, static::class))) {
                return false;
            }

            $attributeArguments = AttributeResolver::getFunctionAttributeArguments($reflectionFunction, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            return $instance ?? false;
        });
    }

    /**
     * @param  ReflectionMethod    $reflectionMethod
     * @return Promise<self|false>
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod): Promise {
        return call(function() use ($reflectionMethod) {
            if (!($trueClassName = AttributeResolver::issetMethodAttribute($reflectionMethod, static::class))) {
                return false;
            }
            $attributeArguments = AttributeResolver::getMethodAttributeArguments($reflectionMethod, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            return $instance ?? false;
        });
    }

    /**
     * @param  ReflectionClass     $reflectionClass
     * @return Promise<self|false>
     */
    public static function findByClass(ReflectionClass $reflectionClass): Promise {
        return call(function() use ($reflectionClass) {
            if (!($trueClassName = AttributeResolver::issetClassAttribute($reflectionClass, static::class))) {
                return false;
            }
            $attributeArguments = AttributeResolver::getClassAttributeArguments($reflectionClass, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            return $instance ?? false;
        });
    }

    /**
     * @param  ReflectionProperty  $reflectionProperty
     * @return Promise<self|false>
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty): Promise {
        return call(function() use ($reflectionProperty) {
            if (!($trueClassName = AttributeResolver::issetPropertyAttribute($reflectionProperty, static::class))) {
                return false;
            }
            $attributeArguments = AttributeResolver::getPropertyAttributeArguments($reflectionProperty, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            return $instance ?? false;
        });
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Promise<self|false>
     */
    public static function findByParameter(ReflectionParameter $reflectionParameter): Promise {
        return call(function() use ($reflectionParameter) {
            if (!($trueClassName = AttributeResolver::issetParameterAttribute($reflectionParameter, static::class))) {
                return false;
            }
            $attributeArguments = AttributeResolver::getParameterAttributeArguments($reflectionParameter, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            return $instance ?? false;
        });
    }

    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context): Promise {
        return call(fn() => true);
    }

    public function onRouteHandler(ReflectionFunction $reflection, Closure &$value, mixed $context): Promise {
        return call(fn() => true);
    }

    public function onClassInstantiation(ReflectionClass $reflection, mixed &$value, mixed $context): Promise {
        return call(fn() => true);
    }
}