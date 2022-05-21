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
     * @param  ReflectionFunction $reflectionFunction
     * @return Promise
     */
    public static function findByFunction(ReflectionFunction $reflectionFunction): Promise {
        return call(function() use ($reflectionFunction) {
            if (!AttributeResolver::issetFunctionAttribute($reflectionFunction, static::class)) {
                return false;
            }

            $args = AttributeResolver::getFunctionAttributeArguments($reflectionFunction, static::class);

            /** @var ReflectionClass $i */
            /** @var ReflectionMethod $entry */
            [$klass, $entry] = static::entry();
            $object = $klass->newInstance(...$args);
            if ($entry) {
                $parameters = [];
                yield Container::dependencies($entry, $parameters);
                yield \Amp\call(fn() => $entry->invoke($object, ...$parameters));
            }

            return $object ?? false;
        });
    }

    /**
     * @param  ReflectionMethod $reflectionMethod
     * @return Promise
     */
    public static function findByMethod(ReflectionMethod $reflectionMethod): Promise {
        return call(function() use ($reflectionMethod) {
            if (!AttributeResolver::issetMethodAttribute($reflectionMethod, static::class)) {
                return false;
            }
            $args = AttributeResolver::getMethodAttributeArguments($reflectionMethod, static::class);

            /** @var ReflectionClass $i */
            /** @var ReflectionMethod $entry */
            [$klass, $entry] = static::entry();
            $object = $klass->newInstance(...$args);
            if ($entry) {
                $parameters = [];
                yield Container::dependencies($entry, $parameters);
                yield \Amp\call(fn() => $entry->invoke($object, ...$parameters));
            }

            return $object ?? false;
        });
    }

    /**
     * @param  ReflectionClass $reflectionClass
     * @return Promise
     */
    public static function findByClass(ReflectionClass $reflectionClass): Promise {
        return call(function() use ($reflectionClass) {
            if (!AttributeResolver::issetClassAttribute($reflectionClass, static::class)) {
                return false;
            }
            $args = AttributeResolver::getClassAttributeArguments($reflectionClass, static::class);

            /** @var ReflectionClass $i */
            /** @var ReflectionMethod $entry */
            [$klass, $entry] = static::entry();
            $object = $klass->newInstance(...$args);
            if ($entry) {
                $parameters = [];
                yield Container::dependencies($entry, $parameters);
                yield \Amp\call(fn() => $entry->invoke($object, ...$parameters));
            }

            return $object ?? false;
        });
    }

    /**
     * @param  ReflectionProperty $reflectionProperty
     * @return Promise
     */
    public static function findByProperty(ReflectionProperty $reflectionProperty): Promise {
        return call(function() use ($reflectionProperty) {
            if (!AttributeResolver::issetPropertyAttribute($reflectionProperty, static::class)) {
                return false;
            }
            $args = AttributeResolver::getPropertyAttributeArguments($reflectionProperty, static::class);

            /** @var ReflectionClass $i */
            /** @var ReflectionMethod $entry */
            [$klass, $entry] = static::entry();
            $object = $klass->newInstance(...$args);
            if ($entry) {
                $parameters = [];
                yield Container::dependencies($entry, $parameters);
                yield \Amp\call(fn() => $entry->invoke($object, ...$parameters));
            }

            return $object ?? false;
        });
    }

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @return Promise
     */
    public static function findByParameter(ReflectionParameter $reflectionParameter): Promise {
        return call(function() use ($reflectionParameter) {
            if (!AttributeResolver::issetParameterAttribute($reflectionParameter, static::class)) {
                return false;
            }
            $args = AttributeResolver::getParameterAttributeArguments($reflectionParameter, static::class);

            /** @var ReflectionClass $i */
            /** @var ReflectionMethod $entry */
            [$klass, $entry] = static::entry();
            $object = $klass->newInstance(...$args);
            if ($entry) {
                $parameters = [];
                yield Container::dependencies($entry, $parameters);
                yield \Amp\call(fn() => $entry->invoke($object, ...$parameters));
            }

            return $object ?? false;
        });
    }

    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $http): Promise {
        return call(fn() => true);
    }

    public function onRouteHandler(ReflectionFunction $reflection, Closure &$value, mixed $route): Promise {
        return call(fn() => true);
    }
}