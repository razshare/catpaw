<?php

namespace CatPaw\Traits;

use CatPaw\Attributes\AttributeResolver;
use CatPaw\Attributes\Entry;
use CatPaw\Container;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use SplObjectStorage;

trait CoreAttributeDefinition {
    private static SplObjectStorage|false $coreDefinitionCache = false;
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
     * @return array<self>|false
     */
    public static function findAllByFunction(ReflectionFunction $reflectionFunction): array|false {
        self::initializeCache();
        if (!($trueClassNames = AttributeResolver::issetFunctionAttributes($reflectionFunction, static::class))) {
            return false;
        }
        
        $instances = [];

        $allAttributesArguments = AttributeResolver::getFunctionAllAttributesArguments($reflectionFunction, static::class);

        foreach ($trueClassNames as $key => $trueClassName) {
            $attributeArguments = $allAttributesArguments[$key];
            $klass              = new ReflectionClass($trueClassName);
            /** @var object */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods());
            $instances[] = $instance;
        }

        if ($instances) {
            return $instances;
        }

        return false;
    }

    /**
     * @param  ReflectionFunction $reflectionFunction
     * @return self|false
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction): self|false {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionFunction) && $instance = self::$coreDefinitionCache->offsetGet($reflectionFunction)) {
            return $instance;
        }
        if (!($trueClassName = AttributeResolver::issetFunctionAttribute($reflectionFunction, static::class))) {
            return false;
        }

        $attributeArguments = AttributeResolver::getFunctionAttributeArguments($reflectionFunction, static::class);
        $klass              = new ReflectionClass($trueClassName);
        /** @var object */
        $instance = $klass->newInstance(...$attributeArguments);
        Container::entry($instance, $klass->getMethods());
        self::$coreDefinitionCache->attach(
            object: $reflectionFunction,
            info: $instance,
        );
        return $instance;
    }

    /**
     * @param  ReflectionMethod $reflectionMethod
     * @return self|false
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod): self|false {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionMethod) && $instance = self::$coreDefinitionCache->offsetGet($reflectionMethod)) {
            return $instance;
        }
        if (!($trueClassName = AttributeResolver::issetMethodAttribute($reflectionMethod, static::class))) {
            return false;
        }
        $attributeArguments = AttributeResolver::getMethodAttributeArguments($reflectionMethod, static::class);
        $klass              = new ReflectionClass($trueClassName);
        /** @var object */
        $instance = $klass->newInstance(...$attributeArguments);
        Container::entry($instance, $klass->getMethods());
        self::$coreDefinitionCache->attach(
            object: $reflectionMethod,
            info: $instance,
        );
        return $instance;
    }

    /**
     * @param  ReflectionClass $reflectionClass
     * @return self|false
     */
    public static function findByClass(ReflectionClass $reflectionClass):self|false {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionClass) && $instance = self::$coreDefinitionCache->offsetGet($reflectionClass)) {
            return $instance;
        }
        if (!($trueClassName = AttributeResolver::issetClassAttribute($reflectionClass, static::class))) {
            return false;
        }
        $attributeArguments = AttributeResolver::getClassAttributeArguments($reflectionClass, static::class);
        $klass              = new ReflectionClass($trueClassName);
        /** @var object */
        $instance = $klass->newInstance(...$attributeArguments);
        Container::entry($instance, $klass->getMethods());
        self::$coreDefinitionCache->attach(
            object: $reflectionClass,
            info: $instance,
        );
        return $instance;
    }

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @return self|false
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty):self|false {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionProperty) && $instance = self::$coreDefinitionCache->offsetGet($reflectionProperty)) {
            return $instance;
        }
        if (!($trueClassName = AttributeResolver::issetPropertyAttribute($reflectionProperty, static::class))) {
            return false;
        }
        $attributeArguments = AttributeResolver::getPropertyAttributeArguments($reflectionProperty, static::class);
        $klass              = new ReflectionClass($trueClassName);
        /** @var object */
        $instance = $klass->newInstance(...$attributeArguments);
        Container::entry($instance, $klass->getMethods());
        self::$coreDefinitionCache->attach(
            object: $reflectionProperty,
            info: $instance,
        );
        return $instance;
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return self|false
     */
    public static function findByParameter(ReflectionParameter $reflectionParameter): self|false {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionParameter) && $instance = self::$coreDefinitionCache->offsetGet($reflectionParameter)) {
            return $instance;
        }
        if (!($trueClassName = AttributeResolver::issetParameterAttribute($reflectionParameter, static::class))) {
            return false;
        }
        $attributeArguments = AttributeResolver::getParameterAttributeArguments($reflectionParameter, static::class);
        $klass              = new ReflectionClass($trueClassName);
        /** @var object */
        $instance = $klass->newInstance(...$attributeArguments);
        Container::entry($instance, $klass->getMethods());
        self::$coreDefinitionCache->attach(
            object: $reflectionParameter,
            info: $instance,
        );
        return $instance;
    }

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        return;
    }

    public function onRouteMount(ReflectionFunction $reflection, Closure &$value, mixed $context) {
        return;
    }

    public function onClassMount(ReflectionClass $reflection, mixed &$value, mixed $context) {
        return;
    }
}