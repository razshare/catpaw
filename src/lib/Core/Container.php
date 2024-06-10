<?php

namespace CatPaw\Core;

use function Amp\File\isDirectory;
use function Amp\File\isFile;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Server\RequestHandler;
use Attribute;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\Attributes\Singleton;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;
use CatPaw\Core\Interfaces\StorageInterface;
use CatPaw\Store\Readable;
use CatPaw\Store\Writable;
use CatPaw\Web\Implementations\ByteRange\SimpleByteRange;
use CatPaw\Web\Implementations\FileServer\SimpleFileServer;
use CatPaw\Web\Implementations\HttpInvoker\SimpleHttpInvoker;
use CatPaw\Web\Implementations\OpenApi\SimpleOpenApi;
use CatPaw\Web\Implementations\OpenApiState\SimpleOpenApiState;
use CatPaw\Web\Implementations\RequestHandler\SimpleRequestHandler;
use CatPaw\Web\Implementations\Router\SimpleRouter;
use CatPaw\Web\Implementations\RouteResolver\SimpleRouteResolver;
use CatPaw\Web\Implementations\Server\SimpleServer;
use CatPaw\Web\Implementations\ViewEngine\LatteViewEngine;
use CatPaw\Web\Interfaces\ByteRangeInterface;
use CatPaw\Web\Interfaces\FileServerInterface;
use CatPaw\Web\Interfaces\HttpInvokerInterface;
use CatPaw\Web\Interfaces\OpenApiInterface;
use CatPaw\Web\Interfaces\OpenApiStateInterface;
use CatPaw\Web\Interfaces\RouteResolverInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\ViewEngineInterface;
use Closure;
use Phar;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;
use function trim;

class Container {
    private function __construct() {
    }

    /**
     * Run the entry method of an instance of a class.
     * @param  object                  $instance
     * @param  array<ReflectionMethod> $methods  methods of the class.
     * @return Unsafe<mixed>
     */
    public static function entry(object $instance, array $methods):Unsafe {
        try {
            foreach ($methods as $method) {
                $entry = Entry::findByMethod($method)->unwrap($error);
                if ($error) {
                    return error($error);
                }
                if ($entry) {
                    $arguments = Container::dependencies($method)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }

                    if ($method->isStatic()) {
                        $result = $method->invoke(null, ...$arguments);
                    } else {
                        $result = $method->invoke($instance, ...$arguments);
                    }
                    if ($result instanceof Unsafe) {
                        return $result;
                    }
                    return ok($result);
                }
            }
            return ok();
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionFunction|ReflectionMethod           $reflection
     * @param  DependenciesOptions                           $options
     * @return Unsafe<array<int,DependencySearchResultItem>>
     */
    private static function findFunctionDependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        DependenciesOptions $options,
    ):Unsafe {
        try {
            $defaultArguments     = $options->defaultArguments;
            $reflectionParameters = $reflection->getParameters();
            $items                = [];
            foreach ($reflectionParameters as $key => $reflectionParameter) {
                $defaultArgument = $defaultArguments[$key] ?? null;
                $isOptional      = $reflectionParameter->isOptional();
                $defaultValue    = $reflectionParameter->isDefaultValueAvailable()?$reflectionParameter->getDefaultValue():null;
                $unwrappedType   = ReflectionTypeManager::unwrap($reflectionParameter);
                $type            = $unwrappedType?$unwrappedType->getName():'';
                $name            = $reflectionParameter->getName();
                $attributes      = $reflectionParameter->getAttributes();

                if ('bool' === $type) {
                    $negative = false;
                } else {
                    $negative = null;
                }

                $defaultCalculated = ($defaultArgument ?? $negative)?$defaultArgument:$negative;

                if (!$defaultCalculated && $isOptional) {
                    $defaultValue = $defaultValue ?? $negative;
                } else {
                    $defaultValue = $defaultCalculated;
                }

                $items[] = new DependencySearchResultItem(
                    reflectionParameter: $reflectionParameter,
                    defaultArgument: $defaultArgument,
                    isOptional: $isOptional,
                    defaultValue: $defaultValue,
                    type: $type,
                    name: $name,
                    attributes: $attributes,
                );
            }

            return ok($items);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionFunction|ReflectionMethod $reflection
     * @param  false|DependenciesOptions           $options
     * @return Unsafe<array<int,mixed>>
     */
    public static function dependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        false|DependenciesOptions $options = false,
    ):Unsafe {
        if (!$options) {
            $options = DependenciesOptions::create(
                key: '',
                overwrites: [],
                provides: [],
                fallbacks: [],
                defaultArguments: [],
                context: false,
            );
        }
        $parameters = [];
        $results    = self::findFunctionDependencies($reflection, $options)->unwrap($error);
        if ($error) {
            return error($error);
        }

        foreach ($results as $key => $result) {
            $reflectionParameter = $result->reflectionParameter;
            $type                = $result->type;
            // $name                = $result->name;
            // $defaultValue        = $result->defaultValue;
            $provide            = $options->provides[$type]   ?? false;
            $overwrite          = $options->overwrites[$type] ?? false;
            $fallback           = $options->fallbacks[$type]  ?? false;
            $attributes         = $result->attributes;
            $numberOfAttributes = count($result->attributes);

            if ($overwrite) {
                $parameters[$key] = $overwrite($result);
                continue;
            }

            if ($provide) {
                $parameters[$key] = $provide($result);
            }

            if (
                "string"           !== $type
                && "int"           !== $type
                && "float"         !== $type
                && "bool"          !== $type
                && "array"         !== $type
                && ""              !== $type
                && Writable::class !== $type
                && Readable::class !== $type
            ) {
                // @phpstan-ignore-next-line
                $instance = Container::get($type)->unwrap($error);
                if ($error) {
                    return error($error);
                }
                $parameters[$key] = $instance;
            }

            if (0 === $numberOfAttributes) {
                if ($fallback) {
                    $parameters[$key] = $fallback($result);
                }
                continue;
            }
            try {
                foreach ($attributes as $attribute) {
                    $attributeReflectionClass = new ReflectionClass($attribute->getName());
                    $findByParameter          = $attributeReflectionClass->getMethod('findByParameter')->getClosure();

                    /** @var callable(ReflectionParameter):(Unsafe<null|AttributeInterface>) $findByParameter */
                    /** @var null|AttributeInterface $attributeInstance */

                    $attributeInstance = $findByParameter($reflectionParameter)->unwrap($error);

                    if ($error) {
                        return error($error);
                    }

                    if (!$attributeInstance) {
                        if ($fallback) {
                            $parameters[$key] = $fallback($result);
                        }
                        continue;
                    }

                    if ($attributeInstance instanceof OnParameterMount) {
                        $attributeInstance->onParameterMount($reflectionParameter, $parameters[$key], $options)->unwrap($error);
                        if ($error) {
                            return error($error);
                        }
                    }

                    if ($parameters[$key] instanceof StorageInterface) {
                        $parameters[$key] = &$parameters[$key]->getStorage();
                    }
                }
            } catch(Throwable $e) {
                return error($e);
            }
        }

        return ok($parameters);
    }

    /**
     * Load libraries and create singletons for the container.
     * @param  string                $path
     * @param  bool                  $clear if true, the container will be cleared before loading.
     * @return Unsafe<array<string>> list of files scanned.
     */
    public static function load(
        string $path,
        bool $clear = false,
    ):Unsafe {
        if ($clear) {
            Container::clear();
        }

        if (!Container::isProvidedOrExists(LoggerInterface::class)) {
            $logger = LoggerFactory::create()->unwrap($error);
            if ($error) {
                return error($error);
            }
            Container::provide(LoggerInterface::class, $logger);
        } else {
            $logger = Container::get(LoggerInterface::class)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        /** @var LoggerInterface $logger */

        $isPhar = isPhar();
        if ('' === trim($path)) {
            /** @var Unsafe<array<string>> */
            return ok([]);
        }

        if ($isPhar) {
            $path = Phar::running()."/$path";
        }

        if (isFile($path)) {
            require_once($path);
            /** @var Unsafe<array<string>> */
            return ok([$path]);
        } else if (!isDirectory($path)) {
            /** @var Unsafe<array<string>> */
            return ok([]);
        }

        $flatList = Directory::flat($path)->unwrap($error);
        if ($error) {
            return  error($error);
        }

        $phpFileNames = [];

        foreach ($flatList as $fileName) {
            if (str_ends_with(strtolower($fileName), '.php')) {
                continue;
            }
            $phpFileNames[] = $fileName;
        }
        /** @var Unsafe<array<string>> */
        return ok($phpFileNames);
    }

    /**
     * Execute all attributes attached to the function.
     *
     * Will not execute the function itself and parameter attributes will not be instantiated at all.
     * @param  Closure|ReflectionFunction $function
     * @return Unsafe<None>
     */
    public static function touch(Closure|ReflectionFunction $function): Unsafe {
        try {
            if ($function instanceof Closure) {
                $reflection = new ReflectionFunction($function);
            } else {
                $reflection = $function;
                $function   = $reflection->getClosure();
            }

            foreach ($reflection->getAttributes() as $attribute) {
                $attributeArguments = $attribute->getArguments();
                /** @var class-string $className */
                $className = $attribute->getName();
                $klass     = new ReflectionClass($className);
                /**
                 * @psalm-suppress ArgumentTypeCoercion
                 */
                $entry  = self::findEntryMethod($klass);
                $object = $klass->newInstance(...$attributeArguments);
                if ($entry) {
                    $arguments = Container::dependencies($entry)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $entry->invoke($object, ...$arguments);
                }
            }
            return ok();
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * Inject dependencies into a function and invoke it.
     * @param  Closure|ReflectionFunction $function
     * @param  bool                       $touch    if true, Container::touch will be
     *                                              called automatically on the function
     * @return Unsafe<mixed>
     */
    public static function run(
        Closure|ReflectionFunction $function,
        bool $touch = true,
    ):Unsafe {
        try {
            if ($function instanceof Closure) {
                $reflection = new ReflectionFunction($function);
            } else {
                $reflection = $function;
                $function   = $reflection->getClosure();
            }

            if ($touch) {
                self::touch($function);
            }

            $arguments = Container::dependencies($reflection)->unwrap($error);
            if ($error) {
                return error($error);
            }

            $result = $function(...$arguments);

            if ($result instanceof Unsafe) {
                return $result;
            }

            return ok($result);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * @param  ReflectionClass        $reflectionClass
     * @return ReflectionMethod|false
     */
    // @phpstan-ignore-next-line
    private static function findEntryMethod(ReflectionClass $reflectionClass): ReflectionMethod|false {
        foreach ($reflectionClass->getMethods() as $method) {
            if (($attributes = $method->getAttributes(Entry::class))) {
                // @phpstan-ignore-next-line
                if (count($attributes) > 0) {
                    return $method;
                }
            }
        }
        return false;
    }

    /**
     * Check if a name is provided or if it matches any singleton (in that order).
     * @param  string $name
     * @return bool
     */
    public static function isProvidedOrExists(string $name): bool {
        return  Provider::exists($name) || Singleton::exists($name);
    }

    /**
     * Set a provider.
     * @param  string          $name  The name to provide.
     * @param  callable|object $value If it's an object the container will provide the object as a singleton, 
     *                                otherwise if it's a callable the container will invoke it to provide the dependency.
     * @return void
     */
    public static function provide(string $name, callable|object $value): void {
        if (is_callable($value)) {
            Singleton::unset($name);
            Provider::set($name, $value);
            return;
        }
        Provider::unset($name);
        Singleton::set($name, $value);
    }

    /**
     * Remove all providers and cached services and singletons.
     * @return void
     */
    public static function clear(): void {
        Singleton::clear();
        Provider::clear();
    }


    private static bool $defaultsProvided = false;

    /**
     * Load defaults providers and singletons.
     * @param  string       $name
     * @return Unsafe<None>
     */
    public static function loadDefaults(string $name):Unsafe {
        if (self::$defaultsProvided) {
            return ok();
        }

        return anyError(function() use ($name) {
            $logger = LoggerFactory::create($name)->try();
            Container::provide(LoggerInterface::class, $logger);
            
            Container::provide(HttpClient::class, fn () => HttpClientBuilder::buildDefault());

            $byteRange = new SimpleByteRange(logger: $logger);
            Container::provide(ByteRangeInterface::class, $byteRange);
            
            $openApiState = new SimpleOpenApiState();
            Container::provide(OpenApiStateInterface::class, $openApiState);
            
            $openApi = new SimpleOpenApi(openApiState: $openApiState);
            Container::provide(OpenApiInterface::class, $openApi);
            
            $router = new SimpleRouter(openApiState: $openApiState);
            Container::provide(RouterInterface::class, $router);
            
            $viewEngine = new LatteViewEngine(logger: $logger);
            Container::provide(ViewEngineInterface::class, $viewEngine);
            
            $httpInvoker = new SimpleHttpInvoker(viewEngine:$viewEngine);
            Container::provide(HttpInvokerInterface::class, $httpInvoker);
            
            $routeResolver = new SimpleRouteResolver(router: $router, httpInvoker: $httpInvoker);
            Container::provide(RouteResolverInterface::class, $routeResolver);
            
            $fileServer = new SimpleFileServer(logger: $logger, byteRange: $byteRange);
            Container::provide(FileServerInterface::class, $fileServer);
            
            $requestHandler = new SimpleRequestHandler(logger: $logger, fileServer: $fileServer, routeResolver: $routeResolver);
            Container::provide(RequestHandler::class, $requestHandler);
            
            $server = new SimpleServer(
                router: $router,
                logger: $logger,
                routeResolver: $routeResolver,
                requestHandler: $requestHandler,
                viewEngine: $viewEngine,
            );
            Container::provide(ServerInterface::class, $server);
            
            self::$defaultsProvided = true;
            return ok();
        });
    }

    /**
     * Get an instance of the given class.
     * 
     * - Services and Singletons are backed by an internal cache, which you can reset by invoking `Container::clear()`.
     * - This method will take care of dependency injections.
     * - Providers' results are not cached.
     * @template T
     * @param  class-string<T> $name
     * @param  mixed           $args
     * @return Unsafe<T>
     */
    public static function get(string $name, ...$args):Unsafe {
        if (Singleton::exists($name)) {
            return ok(Singleton::get($name));
        }

        if (Provider::exists($name)) {
            return ok(Provider::get($name)(...$args));
        }

        if ('callable' === $name) {
            return error("Cannot instantiate callables.");
        } else if ('object' === $name || 'stdClass' === $name) {
            /** @var Unsafe<T> */
            return ok((object)[]);
        }

        $all        = Singleton::getAll();
        $reflection = new ReflectionClass($name);

        if (AttributeResolver::issetClassAttribute($reflection, Attribute::class)) {
            return error("Cannot instantiate $name because it's meant to be an attribute.");
        }

        /** @var Singleton $singleton */
        $singleton = Singleton::findByClass($reflection)->unwrap($error);
        if ($error) {
            return error($error);
        }

        /** @var Service $service */
        $service = Service::findByClass($reflection)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $constructor = $reflection->getConstructor() ?? false;

        if ($constructor) {
            $dependencies = self::dependencies($constructor)->unwrap($error);
            if ($error) {
                return error($error);
            }
        } else {
            $dependencies = [];
        }

        $instance = null;

        // @phpstan-ignore-next-line
        if ($singleton || $service) {
            // @phpstan-ignore-next-line
            if ($service) {
                $service->onClassInstantiation($reflection, $instance, $dependencies)->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
        } else {
            return error("Cannot instantiate $name because it's not marked as a service or a singleton.");
        }

        if (!$instance) {
            return error("Instance of $name is null.");
        }

        self::entry($instance, $reflection->getMethods())->unwrap($error);

        if ($error) {
            return error($error);
        }

        return ok($instance);
    }
}
