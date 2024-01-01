<?php

namespace CatPaw\Traits;

use CatPaw\AttributeResolver;
use CatPaw\Attributes\Entry;
use CatPaw\Container;
use CatPaw\DependenciesOptions;
use CatPaw\Unsafe;
use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use SplObjectStorage;
use Throwable;

use function CatPaw\error;
use function CatPaw\ok;

trait CoreAttributeDefinition {
    private static SplObjectStorage|false $coreDefinitionCache = false;
    private static function initializeCache():void {
        if (!self::$coreDefinitionCache) {
            self::$coreDefinitionCache = new SplObjectStorage();
        }
    }
    

    /**
     * @param  ReflectionFunction $reflectionFunction
     * @return Unsafe<array<self>>
     */
    public static function findAllByFunction(ReflectionFunction $reflectionFunction): Unsafe {
        self::initializeCache();
        if (!($trueClassNames = AttributeResolver::issetFunctionAttributes($reflectionFunction, static::class))) {
            return ok([]);
        }
        

        try{
            $instances = [];
    
            $allAttributesArguments = AttributeResolver::getFunctionAllAttributesArguments($reflectionFunction, static::class);
            
            foreach ($trueClassNames as $key => $trueClassName) {
                $attributeArguments = $allAttributesArguments[$key];
                $klass              = new ReflectionClass($trueClassName);
                /** @var object */
                $instance = $klass->newInstance(...$attributeArguments);
                if($error = Container::entry($instance, $klass->getMethods())->error){
                    return error($error);
                }
                $instances[] = $instance;
            }
            return ok($instances);
        } catch(Throwable $e){
            return error($e);
        }
    }

    /**
     * @param  ReflectionFunction $reflectionFunction
     * @return Unsafe<false|self>
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction): Unsafe {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionFunction) && $instance = self::$coreDefinitionCache->offsetGet($reflectionFunction)) {
            return ok($instance);
        }
        if (!($trueClassName = AttributeResolver::issetFunctionAttribute($reflectionFunction, static::class))) {
            return ok(false);
        }

        try{
            $attributeArguments = AttributeResolver::getFunctionAttributeArguments($reflectionFunction, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var object */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionFunction,
                info: $instance,
            );
            return ok($instance);
        }catch(Throwable $e){
            return error($e);
        }
    }

    /**
     * @param  ReflectionMethod $reflectionMethod
     * @return Unsafe<self|false>
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod):Unsafe {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionMethod) && $instance = self::$coreDefinitionCache->offsetGet($reflectionMethod)) {
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::issetMethodAttribute($reflectionMethod, static::class))) {
            return ok(false);
        }

        try{
            $attributeArguments = AttributeResolver::getMethodAttributeArguments($reflectionMethod, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var object */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionMethod,
                info: $instance,
            );
            return ok($instance);

        }catch(Throwable $e){
            return error($e);
        }
    }

    /**
     * @param  ReflectionClass $reflectionClass
     * @return Unsafe<self|false>
     */
    public static function findByClass(ReflectionClass $reflectionClass):Unsafe {
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionClass) && $instance = self::$coreDefinitionCache->offsetGet($reflectionClass)) {
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::issetClassAttribute($reflectionClass, static::class))) {
            return ok(false);
        }

        try{
            $attributeArguments = AttributeResolver::getClassAttributeArguments($reflectionClass, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var object */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionClass,
                info: $instance,
            );
            return ok($instance);
        }catch(Throwable $e){
            return error($e);
        }
    }

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @return Unsafe<self|false>
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty):Unsafe{
        self::initializeCache();
        if (self::$coreDefinitionCache->contains($reflectionProperty) && $instance = self::$coreDefinitionCache->offsetGet($reflectionProperty)) {
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::issetPropertyAttribute($reflectionProperty, static::class))) {
            return ok(false);
        }

        try {
            $attributeArguments = AttributeResolver::getPropertyAttributeArguments($reflectionProperty, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var object */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionProperty,
                info: $instance,
            );
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
        if (self::$coreDefinitionCache->contains($reflectionParameter) && $instance = self::$coreDefinitionCache->offsetGet($reflectionParameter)) {
            return ok($instance);
        }

        if (!($trueClassName = AttributeResolver::issetParameterAttribute($reflectionParameter, static::class))) {
            return ok(false);
        }
        
        try {
            $attributeArguments = AttributeResolver::getParameterAttributeArguments($reflectionParameter, static::class);
            $klass              = new ReflectionClass($trueClassName);
            /** @var object */
            $instance = $klass->newInstance(...$attributeArguments);
            Container::entry($instance, $klass->getMethods());
            self::$coreDefinitionCache->attach(
                object: $reflectionParameter,
                info: $instance,
            );
            return ok($instance);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):void {
    }

    public function onFunctionMount(ReflectionFunction $reflection, Closure &$value, DependenciesOptions $options):void {
    }

    public function onClassMount(ReflectionClass $reflection, mixed &$value, DependenciesOptions $options):void {
    }
}