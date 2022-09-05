<?php

namespace CatPaw\Attributes\Traits;

use function Amp\call;
use Amp\Promise;
use CatPaw\Attributes\{AttributeResolver, Entry};
use CatPaw\Utilities\Container;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use SplObjectStorage;

trait CoreAttributeDefinition {
    private static ?SplObjectStorage $coreDefinitionCache = null;
    private static function initializeCache():void {
        if (!self::$coreDefinitionCache) {
            self::$coreDefinitionCache = new SplObjectStorage();
        }
    }
    /**
     * @return array|false
     */
    private static function entry(): array|false {
        self::initializeCache();
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
     * @param  ReflectionFunction $reflectionFunction
     * @return Promise<self|null>
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction): Promise {
        self::initializeCache();
        return call(function() use ($reflectionFunction) {
            if (self::$coreDefinitionCache->contains($reflectionFunction) && $instance = self::$coreDefinitionCache->offsetGet($reflectionFunction)) {
                return $instance;
            }
            if (!($trueClassName = AttributeResolver::issetFunctionAttribute($reflectionFunction, static::class))) {
                return null;
            }

            $attributeArguments = AttributeResolver::getFunctionAttributeArguments($reflectionFunction, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionFunction,
                info: $instance,
            );
            return $instance;
        });
    }

    /**
     * @param  ReflectionMethod   $reflectionMethod
     * @return Promise<self|null>
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod): Promise {
        self::initializeCache();
        return call(function() use ($reflectionMethod) {
            if (self::$coreDefinitionCache->contains($reflectionMethod) && $instance = self::$coreDefinitionCache->offsetGet($reflectionMethod)) {
                return $instance;
            }
            if (!($trueClassName = AttributeResolver::issetMethodAttribute($reflectionMethod, static::class))) {
                return null;
            }
            $attributeArguments = AttributeResolver::getMethodAttributeArguments($reflectionMethod, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionMethod,
                info: $instance,
            );
            return $instance;
        });
    }

    /**
     * @param  ReflectionClass    $reflectionClass
     * @return Promise<self|null>
     */
    public static function findByClass(ReflectionClass $reflectionClass): Promise {
        self::initializeCache();
        return call(function() use ($reflectionClass) {
            if (self::$coreDefinitionCache->contains($reflectionClass) && $instance = self::$coreDefinitionCache->offsetGet($reflectionClass)) {
                return $instance;
            }
            if (!($trueClassName = AttributeResolver::issetClassAttribute($reflectionClass, static::class))) {
                return null;
            }
            $attributeArguments = AttributeResolver::getClassAttributeArguments($reflectionClass, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionClass,
                info: $instance,
            );
            return $instance;
        });
    }

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @return Promise<self|null>
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty): Promise {
        self::initializeCache();
        return call(function() use ($reflectionProperty) {
            if (self::$coreDefinitionCache->contains($reflectionProperty) && $instance = self::$coreDefinitionCache->offsetGet($reflectionProperty)) {
                return $instance;
            }
            if (!($trueClassName = AttributeResolver::issetPropertyAttribute($reflectionProperty, static::class))) {
                return null;
            }
            $attributeArguments = AttributeResolver::getPropertyAttributeArguments($reflectionProperty, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionProperty,
                info: $instance,
            );
            return $instance;
        });
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Promise<self|null>
     */
    public static function findByParameter(ReflectionParameter $reflectionParameter): Promise {
        self::initializeCache();
        return call(function() use ($reflectionParameter) {
            if (self::$coreDefinitionCache->contains($reflectionParameter) && $instance = self::$coreDefinitionCache->offsetGet($reflectionParameter)) {
                return $instance;
            }
            if (!($trueClassName = AttributeResolver::issetParameterAttribute($reflectionParameter, static::class))) {
                return null;
            }
            $attributeArguments = AttributeResolver::getParameterAttributeArguments($reflectionParameter, static::class);
            $klass              = new ReflectionClass($trueClassName);
            $instance           = $klass->newInstance(...$attributeArguments);
            yield Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionParameter,
                info: $instance,
            );
            return $instance;
        });
    }

    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        return;
    }

    public function onRouteHandler(ReflectionFunction $reflection, Closure &$value, mixed $context) {
        return;
    }

    public function onClassInstantiation(ReflectionClass $reflection, mixed &$value, mixed $context) {
        return;
    }
}