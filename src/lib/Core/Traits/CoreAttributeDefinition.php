<?php

namespace CatPaw\Core\Traits;

use CatPaw\Core\AttributeResolver;
use CatPaw\Core\Container;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

use SplObjectStorage;
use Throwable;

trait CoreAttributeDefinition {
    /** @var SplObjectStorage<object,object> */
    private static false|SplObjectStorage $coreDefinitionCache = false;
    /**
     * @return void
     */
    private static function initializeCache():void {
        if (!self::$coreDefinitionCache) {
            self::$coreDefinitionCache = new SplObjectStorage();
        }
    }


    /**
     * @param  ReflectionFunction  $reflectionFunction
     * @return Unsafe<array<self>>
     */
    public static function findAllByFunction(ReflectionFunction $reflectionFunction): Unsafe {
        self::initializeCache();
        if (!($trueClassNames = AttributeResolver::functionAttributes($reflectionFunction, static::class))) {
            /** @var Unsafe<array<self>> */
            return ok([]);
        }

        try {
            /** @var array<self> $instances */
            $instances = [];

            $allAttributesArguments = AttributeResolver::functionAllAttributesArguments($reflectionFunction, static::class);

            foreach ($trueClassNames as $key => $trueClassName) {
                $attributeArguments = $allAttributesArguments[$key];
                $klass              = new ReflectionClass($trueClassName);
                $instance           = $klass->newInstance(...$attributeArguments);
                Container::entry($instance, $klass->getMethods())->unwrap($error);
                if ($error) {
                    return error($error);
                }
                $instances[] = $instance;
            }
            /** @var Unsafe<array<self>> */
            return ok($instances);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionFunction $reflectionFunction
     * @return Unsafe<false|self>
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction): Unsafe {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionFunction)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionFunction);
            /** @var Unsafe<false|self> */
            return ok($instance);
        }
        if (!($trueClassName = AttributeResolver::functionAttribute($reflectionFunction, static::class))) {
            /** @var Unsafe<false|self> */
            return ok(false);
        }

        try {
            $attributeArguments = AttributeResolver::functionAttributeArguments($reflectionFunction, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var self $instance */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods())->unwrap($error);
            if ($error) {
                return error($error);
            }
            self::$coreDefinitionCache->attach(
                object: $reflectionFunction,
                info: $instance,
            );
            /** @var Unsafe<false|self> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionMethod   $reflectionMethod
     * @return Unsafe<self|false>
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod):Unsafe {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionMethod)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionMethod);
            /** @var Unsafe<self|false> */
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::methodAttribute($reflectionMethod, static::class))) {
            /** @var Unsafe<self|false> */
            return ok(false);
        }

        try {
            $attributeArguments = AttributeResolver::methodAttributeArguments($reflectionMethod, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var self $instance */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods())->unwrap($error);
            if ($error) {
                return error($error);
            }
            self::$coreDefinitionCache->attach(
                object: $reflectionMethod,
                info: $instance,
            );
            /** @var Unsafe<self|false> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionClass<object> $reflectionClass
     * @return Unsafe<self|false>
     */
    public static function findByClass(ReflectionClass $reflectionClass):Unsafe {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionClass)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionClass);
            /** @var Unsafe<self|false> */
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::classAttribute($reflectionClass, static::class))) {
            /** @var Unsafe<self|false> */
            return ok(false);
        }

        try {
            $attributeArguments = AttributeResolver::classAttributeArguments($reflectionClass, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var self $instance */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods())->unwrap($error);
            if ($error) {
                return error($error);
            }
            self::$coreDefinitionCache->attach(
                object: $reflectionClass,
                info: $instance,
            );
            /** @var Unsafe<self|false> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @return Unsafe<self|false>
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty):Unsafe {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionProperty)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionProperty);
            /** @var Unsafe<self|false> */
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::propertyAttribute($reflectionProperty, static::class))) {
            /** @var Unsafe<self|false> */
            return ok(false);
        }

        try {
            $attributeArguments = AttributeResolver::propertyAttributeArguments($reflectionProperty, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var self $instance */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods())->unwrap($error);
            if ($error) {
                return error($error);
            }
            self::$coreDefinitionCache->attach(
                object: $reflectionProperty,
                info: $instance,
            );
            /** @var Unsafe<self|false> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Unsafe<self|false>
     */
    public static function findByParameter(ReflectionParameter $reflectionParameter):Unsafe {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionParameter)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionParameter);
            /** @var Unsafe<self|false> */
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::parameterAttribute($reflectionParameter, static::class))) {
            /** @var Unsafe<self|false> */
            return ok(false);
        }

        try {
            $attributeArguments = AttributeResolver::parameterAttributeArguments($reflectionParameter, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var self $instance */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods())->unwrap($error);
            if ($error) {
                return error($error);
            }
            self::$coreDefinitionCache->attach(
                object: $reflectionParameter,
                info: $instance,
            );
            /** @var Unsafe<self|false> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Unsafe<array<self>>
     */
    public static function findAllByParameter(ReflectionParameter $reflectionParameter): Unsafe {
        self::initializeCache();
        if (!($trueClassNames = AttributeResolver::parameterAttributes($reflectionParameter, static::class))) {
            /** @var Unsafe<array<self>> */
            return ok([]);
        }

        try {
            $instances = [];

            $allAttributesArguments = AttributeResolver::parameterAllAttributeArguments($reflectionParameter, static::class);

            foreach ($trueClassNames as $key => $trueClassName) {
                $attributeArguments = $allAttributesArguments[$key];
                $klass              = new ReflectionClass($trueClassName);
                /** @var self $instance */
                $instance = $klass->newInstance(...$attributeArguments);
                Container::entry($instance, $klass->getMethods())->unwrap($error);
                if ($error) {
                    return error($error);
                }
                $instances[] = $instance;
            }
            /** @var Unsafe<array<self>> */
            return ok($instances);
        } catch(Throwable $e) {
            return error($e);
        }
    }
}
