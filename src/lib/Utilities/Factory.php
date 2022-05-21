<?php

namespace CatPaw\Utilities;

use function Amp\call;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Attribute;
use CatPaw\Attributes\AttributeResolver;
use CatPaw\Attributes\Entry;
use CatPaw\Attributes\Service;
use CatPaw\Attributes\Singleton;
use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionUnionType;

use SplFixedArray;

class Factory {
    private static array $singletons = [];
    private static array $args = [];

    public static function isset(string $className): bool {
        return isset(self::$singletons[$className]);
    }

    public static function setObject(string $className, mixed $object): void {
        self::$singletons[$className] = $object;
    }

    /**
     * @deprecated use Factory::create instead
     * @param  string       $className
     * @param  null|Closure $args
     * @return void
     */
    public static function setConstructorInjector(string $className, ?Closure $args = null): void {
        self::$args[$className] = $args;
    }

    /**
     * @deprecated along with `Factory::setConstructorInjector`.
     * @param  string  $className
     * @return Closure
     */
    public static function getConstructorInjector(string $className): Closure {
        if (!isset(self::$args[$className])) {
            return fn() => [];
        }
        return self::$args[$className];
    }

    private static array $cache = [];
    private const PARAMETERS_INIT_VALUE = 0;
    private const REFLECTION_PARAMETERS = 1;
    private const PARAMETERS_LEN = 2;
    private const PARAMETERS_CNAMES = 3;
    private const PARAMETERS_ATTRIBUTES_LEN = 4;
    private const PARAMETERS_ATTRIBUTES_CLOSURES = 5;
    private const PARAMETERS_ATTRIBUTES_HAVE_STORAGE = 6;

    /**
     * @throws ReflectionException
     */
    private static function entry(array $methods, mixed $instance) {
        /** @var ReflectionMethod $method */
        foreach ($methods as $method) {
            $entry = yield Entry::findByMethod($method);
            if ($entry) {
                if ($method instanceof ReflectionMethod) {
                    $args = [];
                    $i = 0;
                    foreach ($method->getParameters() as $parameter) {
                        $args[$i] = yield Factory::create($parameter->getType()->getName());
                        $i++;
                    }
                    if ($method->isStatic()) {
                        yield \Amp\call(fn() => $method->invoke(null, ...$args));
                    } else {
                        yield \Amp\call(fn() => $method->invoke($instance, ...$args));
                    }
                    break;
                }
            }
        }
    }


    /**
     * @throws ReflectionException
     */
    private static function cacheInMethodOrFunctionDependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        string $key1,
        string $key2,
        array $defaultParameters
    ): void {
        if (!isset(self::$cache[$key1])) {
            self::$cache[$key1] = [];
        }

        if (!isset(self::$cache[$key1][$key2])) {
            self::$cache[$key1][$key2] = [];

            $cache = new SplFixedArray(8);

            $refparams = $reflection->getParameters();
            $len = count($refparams);
            $parameters = array_fill(0, $len, false);

            $cache[self::REFLECTION_PARAMETERS] = $refparams;
            $cache[self::PARAMETERS_LEN] = $len;
            $cache[self::PARAMETERS_INIT_VALUE] = new SplFixedArray($len);
            $cache[self::PARAMETERS_CNAMES] = new SplFixedArray($len);
            $cache[self::PARAMETERS_ATTRIBUTES_LEN] = new SplFixedArray($len);
            $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES] = new SplFixedArray($len);
            $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE] = new SplFixedArray($len);

            for ($i = 0; $i < $len; $i++) {
                $type = $refparams[$i]->getType();
                if ($type instanceof ReflectionUnionType) {
                    $cname = $type->getTypes()[0]->getName();
                } else {
                    $cname = $type ? $type->getName() : '';
                }

                $cache[self::PARAMETERS_CNAMES][$i] = $cname;
                $parameters[$i] = $defaultParameters[$i] ?? $refparams[$i]->isOptional() ? $refparams[$i]->getDefaultValue() : false;
                $cache[self::PARAMETERS_INIT_VALUE][$i] = $parameters[$i];
                $attributes = $refparams[$i]->getAttributes();
                $alen = count($attributes);
                $cache[self::PARAMETERS_ATTRIBUTES_LEN][$i] = $alen;
                $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i] = new SplFixedArray($alen);
                for ($j = 0; $j < $alen; $j++) {
                    $attribute = $attributes[$j];
                    $aname = $attribute->getName();
                    $class = new ReflectionClass($aname);
                    $method = $class->getMethod("findByParameter");

                    $closures = $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES][$i];
                    $closures[$j] = $method->getClosure();
                    $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES][$i] = $closures;

                    $haveStorage = $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i];
                    $haveStorage[$j] = $class->hasMethod("storage");
                    $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i] = $haveStorage;
                }
            }
            self::$cache[$key1][$key2] = $cache;
        }
    }

    public static function dependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        array $defaultParameters,
        mixed $http = false
    ): Promise {
        return call(function() use ($reflection, &$defaultParameters, $http) {
            if ($http) {
                $method = $http->request->getMethod();
                $path = $http->request->getUri()->getPath();
                if (!isset(self::$cache[$method][$path])) {
                    self::cacheInMethodOrFunctionDependencies($reflection, $method, $path, $defaultParameters);
                }
                $cache = &self::$cache[$method][$path];
            } else {
                $fileName = $reflection->getFileName();
                $functionName = $reflection->getName();
                if ($reflection instanceof ReflectionMethod) {
                    $class = $reflection->getDeclaringClass();
                    $functionName = $class->getName().':'.$functionName;
                }
                if (!isset(self::$cache[$fileName][$functionName])) {
                    self::cacheInMethodOrFunctionDependencies($reflection, $fileName, $functionName, $defaultParameters);
                }
                $cache = &self::$cache[$fileName][$functionName];
            }

            $refparams = $cache[self::REFLECTION_PARAMETERS];
            $len = $cache[self::PARAMETERS_LEN];
            $parameters = array_fill(0, $len, false);

            for ($i = 0; $i < $len; $i++) {
                $parameters[$i] = $cache[self::PARAMETERS_INIT_VALUE][$i];
                $cname = $cache[self::PARAMETERS_CNAMES][$i];

                if (Request::class === $cname) {
                    $parameters[$i] = $http->request;
                    continue;
                }

                if (Response::class === $cname) {
                    $parameters[$i] = $http->response;
                    continue;
                }

                if (
                    "string" !== $cname
                    & "int" !== $cname
                    & "float" !== $cname
                    & "bool" !== $cname
                    & "array" !== $cname
                    & "" !== $cname
                ) {
                    $parameters[$i] = yield Factory::create($cname);
                }

                $alen = $cache[self::PARAMETERS_ATTRIBUTES_LEN][$i];

                for ($j = 0; $j < $alen; $j++) {
                    /** @var Closure $closure */
                    $findByParameter = $cache[self::PARAMETERS_ATTRIBUTES_CLOSURES][$i][$j];
                    $attributeInstance = yield $findByParameter($refparams[$i]);
                    if (!$attributeInstance) {
                        continue;
                    }
                    yield $attributeInstance->onParameter($refparams[$i], $parameters[$i], $http);
                    $attributeHasStorage = $cache[self::PARAMETERS_ATTRIBUTES_HAVE_STORAGE][$i][$j];
                    if ($attributeHasStorage) {
                        $parameters[$i] = &$parameters[$i]->storage();
                    }
                }
            }
        });
    }

    /**
     * Make a new instance of the given class.<br />
     * This method will take care of dependency injections.
     * @param  string          $className full name of the class
     * @return Promise<object>
     */
    public static function create(string $className, ...$defaultArguments): Promise {
        return call(function() use ($className, $defaultArguments) {
            if (self::$singletons[$className] ?? false) {
                return self::$singletons[$className];
            }

            $reflection = new ReflectionClass($className);

            if (
                $reflection->isInterface()
                || AttributeResolver::issetClassAttribute($reflection, Attribute::class)
                || count($reflection->getAttributes()) === 0
            ) {
                return false;
            }

            $service = yield Service::findByClass($reflection);
            $singleton = yield Singleton::findByClass($reflection);

            $constructor = $reflection->getConstructor() ?? false;
            if ($constructor) {
                $arguments = yield self::dependencies($constructor, $defaultArguments);
            }
            
            $instance = new $className(...$arguments);

            if ($singleton || $service) {
                self::$singletons[$className] = $instance;
            }

            yield from self::entry($reflection->getMethods(), $instance);

            return $instance ?? false;
        });
    }
}