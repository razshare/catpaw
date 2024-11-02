<?php

namespace CatPaw\Core\Traits;

use CatPaw\Core\AttributeResolver;
use CatPaw\Core\Container;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
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
     * @return Result<array<self>>
     */
    public static function findAllByFunction(ReflectionFunction $reflectionFunction):Result {
        self::initializeCache();
        if (!($trueClassNames = AttributeResolver::functionAttributes($reflectionFunction, static::class))) {
            /** @var Result<array<self>> */
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
            /** @var Result<array<self>> */
            return ok($instances);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionFunction $reflectionFunction
     * @return Result<false|self>
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction):Result {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionFunction)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionFunction);
            /** @var Result<false|self> */
            return ok($instance);
        }
        if (!($trueClassName = AttributeResolver::functionAttribute($reflectionFunction, static::class))) {
            /** @var Result<false|self> */
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
            /** @var Result<false|self> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionMethod   $reflectionMethod
     * @return Result<self|false>
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod):Result {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionMethod)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionMethod);
            /** @var Result<self|false> */
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::methodAttribute($reflectionMethod, static::class))) {
            /** @var Result<self|false> */
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
            /** @var Result<self|false> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionClass<object> $reflectionClass
     * @return Result<self|false>
     */
    public static function findByClass(ReflectionClass $reflectionClass):Result {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionClass)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionClass);
            /** @var Result<self|false> */
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::classAttribute($reflectionClass, static::class))) {
            /** @var Result<self|false> */
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
            /** @var Result<self|false> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @return Result<self|false>
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty):Result {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionProperty)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionProperty);
            /** @var Result<self|false> */
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::propertyAttribute($reflectionProperty, static::class))) {
            /** @var Result<self|false> */
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
            /** @var Result<self|false> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Result<self|false>
     */
    public static function findByParameter(ReflectionParameter $reflectionParameter):Result {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionParameter)) {
            $instance = self::$coreDefinitionCache->offsetGet($reflectionParameter);
            /** @var Result<self|false> */
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::parameterAttribute($reflectionParameter, static::class))) {
            /** @var Result<self|false> */
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
            /** @var Result<self|false> */
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Result<array<self>>
     */
    public static function findAllByParameter(ReflectionParameter $reflectionParameter):Result {
        self::initializeCache();
        if (!($trueClassNames = AttributeResolver::parameterAttributes($reflectionParameter, static::class))) {
            /** @var Result<array<self>> */
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
            /** @var Result<array<self>> */
            return ok($instances);
        } catch(Throwable $e) {
            return error($e);
        }
    }
}
