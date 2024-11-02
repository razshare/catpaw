<?php

namespace CatPaw\Core;

use function Amp\File\isDirectory;
use function Amp\File\isFile;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Attribute;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;
use CatPaw\Core\Interfaces\StorageInterface;
use CatPaw\Store\Readable;
use CatPaw\Store\Writable;
use CatPaw\Web\Interfaces\FileServerOverwriteInterface;
use Closure;
use FFI;
use Phar;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

use ReflectionParameter;
use Throwable;

class Container {
    private function __construct() {
    }

    /**
     * Require libraries.
     * @param  string                $librariesPath Path to the libraries directory.
     * @return Result<array<string>> list of files scanned.
     */
    public static function requireLibraries(string $librariesPath):Result {
        $isPhar = isPhar();
        if ('' === trim($librariesPath)) {
            /** @var Result<array<string>> */
            return ok([]);
        }

        if ($isPhar) {
            $librariesPath = Phar::running()."/$librariesPath";
        }

        if (isFile($librariesPath)) {
            require_once($librariesPath);
            /** @var Result<array<string>> */
            return ok([$librariesPath]);
        } else if (!isDirectory($librariesPath)) {
            /** @var Result<array<string>> */
            return ok([]);
        }

        $flatList = Directory::flat($librariesPath)->unwrap($error);
        if ($error) {
            return  error($error);
        }

        $phpFileNames = [];

        foreach ($flatList as $fileName) {
            if (!str_ends_with(strtolower($fileName), '.php')) {
                continue;
            }
            require_once($fileName);
            $phpFileNames[] = $fileName;
        }
        /** @var Result<array<string>> */
        return ok($phpFileNames);
    }


    /**
     * Load default providers, such as the logger and the http client providers.
     * @param  string       $applicationName Name of the application.
     * @return Result<None>
     */
    public static function loadDefaultProviders(string $applicationName):Result {
        Container::provide(FFI::class, static fn () => new FFI);

        // Making sure providers load correctly.
        $providersLoaded = match (false) {
            class_exists($className = \CatPaw\Core\Implementations\Environment\SimpleEnvironment::class)      => error("Could not load class $className"),
            class_exists($className = \CatPaw\Queue\Implementations\Queue\SimpleQueue::class)                 => error("Could not load class $className"),
            class_exists($className = \CatPaw\RaspberryPi\Implementations\Gpio\SimpleGpio::class)             => error("Could not load class $className"),
            class_exists($className = \CatPaw\Schedule\Implementations\Schedule\SimpleSchedule::class)        => error("Could not load class $className"),
            class_exists($className = \CatPaw\Store\Implementations\Store\SimpleState::class)                 => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\ByteRange\SimpleByteRange::class)           => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\FileServer\SimpleFileServer::class)         => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\FileServer\SpaFileServer::class)            => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\HttpInvoker\SimpleHttpInvoker::class)       => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\OpenApi\SimpleOpenApi::class)               => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\OpenApiState\SimpleOpenApiState::class)     => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\RequestHandler\SimpleRequestHandler::class) => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\RouteResolver\SimpleRouteResolver::class)   => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\Router\SimpleRouter::class)                 => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\Server\SimpleServer::class)                 => error("Could not load class $className"),
            class_exists($className = \CatPaw\Web\Implementations\Websocket\SimpleWebsocket::class)           => error("Could not load class $className"),
            default                                                                                           => ok()
        };

        $providersLoaded->unwrap($error);
        if ($error) {
            return error($error);
        }

        /** @var LoggerInterface $logger */
        $logger = LoggerFactory::create(loggerName: $applicationName)->unwrap($error);
        if ($error) {
            return error($error);
        }

        Container::provide(LoggerInterface::class, $logger);
        Container::provide(HttpClient::class, static function() {
            return HttpClientBuilder::buildDefault();
        });

        return ok();
    }

    /**
     * Run the entry method of an instance of a class.
     * @param  object                  $instance
     * @param  array<ReflectionMethod> $methods  methods of the class.
     * @return Result<mixed>
     */
    public static function entry(object $instance, array $methods):Result {
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
                    if ($result instanceof Result) {
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
     * @return Result<array<int,DependencySearchResultItem>>
     */
    private static function findFunctionDependencies(
        ReflectionFunction|ReflectionMethod $reflection
    ):Result {
        try {
            $reflectionParameters = $reflection->getParameters();
            $items                = [];
            foreach ($reflectionParameters as $key => $reflectionParameter) {
                $unwrappedType = ReflectionTypeManager::unwrap($reflectionParameter);
                $wrappedType   = ReflectionTypeManager::wrap($reflectionParameter);
                $type          = $unwrappedType?$unwrappedType->getName():'';
                if (FileServerOverwriteInterface::class === $type) {
                    echo "here\n";
                }
                $name       = $reflectionParameter->getName();
                $attributes = $reflectionParameter->getAttributes();

                $isOptional   = $wrappedType->allowsDefaultValue() || $wrappedType->allowsNullValue();
                $defaultValue = match ($wrappedType->allowsDefaultValue()) {
                    true    => $wrappedType->defaultValue(),
                    default => match ($wrappedType->allowsBoolean() || $wrappedType->allowsFalse()) {
                        true    => false,
                        default => null,
                    }
                };


                $items[] = new DependencySearchResultItem(
                    reflectionParameter: $reflectionParameter,
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
     * @return Result<array<int,mixed>>
     */
    public static function dependencies(
        ReflectionFunction|ReflectionMethod $reflection,
        false|DependenciesOptions $options = false,
    ):Result {
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
        $results    = self::findFunctionDependencies($reflection)->unwrap($error);
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
                    if (!$result->isOptional) {
                        return error($error);
                    }
                    $logger = Container::get(LoggerInterface::class)->unwrap($loggerError);
                    if ($loggerError) {
                        return error($loggerError);
                    }
                    $logger->warning($error);
                    $instance = $result->defaultValue;
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

                    /** @var callable(ReflectionParameter):(Result<null|AttributeInterface>) $findByParameter */
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
                        $parameters[$key] = &$parameters[$key]->storage();
                    }
                }
            } catch(Throwable $e) {
                return error($e);
            }
        }

        return ok($parameters);
    }

    /**
     * Execute all attributes attached to the function.
     *
     * Will not execute the function itself and parameter attributes will not be instantiated at all.
     * @param  Closure|ReflectionFunction $function
     * @return Result<None>
     */
    public static function touch(Closure|ReflectionFunction $function):Result {
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
     * @return Result<mixed>
     */
    public static function run(
        Closure|ReflectionFunction $function,
        bool $touch = true,
    ):Result {
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

            if ($result instanceof Result) {
                return $result;
            }

            return ok($result);
        } catch(Throwable $error) {
            return error($error);
        }
    }

    /**
     * @param  ReflectionClass        $reflectionClass
     * @return ReflectionMethod|false
     */
    // @phpstan-ignore-next-line
    private static function findEntryMethod(ReflectionClass $reflectionClass):ReflectionMethod|false {
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
     * Check if a name is provided.
     * @param  string $name
     * @return bool
     */
    public static function isProvided(string $name):bool {
        return  Provider::isset($name);
    }

    /**
     * Set a provider.
     * @param  string          $name  The name to provide.
     * @param  callable|object $value If it's an object the container will provide the object as a singleton, 
     *                                otherwise if it's a callable the container will invoke it to provide the dependency.
     * @return void
     */
    public static function provide(string $name, callable|object $value):void {
        if (is_callable($value)) {
            Provider::set($name, $value);
            return;
        }
        Provider::set($name, static fn () => $value);
    }

    /**
     * Remove all providers.
     * @return void
     */
    public static function clearAll():void {
        Provider::clearAll();
    }

    /**
     * Get an instance of the given class.
     * @template T
     * @param  class-string<T> $name
     * @param  mixed           $args
     * @return Result<T>
     */
    public static function get(string $name, ...$args):Result {
        if (Provider::isset($name)) {
            return ok(Provider::get($name)(...$args));
        }

        if (interface_exists($name)) {
            $providerClassName = Provider::findNameByInterface($name)->unwrap($error);
            if ($error) {
                return error($error);
            }
            if (!$providerClassName) {
                return error("Interface `$name` doesn't seem to be provided.\nMake sure you're providing it by invoking `Container::provide()` or by adding the `#[Provider]` attribute to any class that implements it.");
            }

            Provider::withAlias($providerClassName, $name);

            // @phpstan-ignore-next-line
            return self::get($providerClassName, ...$args);
        }

        if ('callable' === $name) {
            return error("Cannot instantiate callables.");
        } else if ('object' === $name || 'stdClass' === $name) {
            /** @var Result<T> */
            return ok((object)[]);
        }

        $reflection = new ReflectionClass($name);

        if (AttributeResolver::classAttribute($reflection, Attribute::class)) {
            return error("Cannot instantiate $name because it's meant to be an attribute.");
        }

        $instance    = null;
        $constructor = $reflection->getConstructor() ?? false;

        if ($constructor) {
            $dependencies = self::dependencies($constructor)->unwrap($dependenciesError);
            if ($dependenciesError) {
                return error($dependenciesError);
            }
        } else {
            $dependencies = [];
        }

        /** @var false|Provider $provider */
        $provider = Provider::findByClass($reflection)->unwrap($error);
        if ($error) {
            return error($error);
        }
        if ($provider) {
            $provider->onClassInstantiation($reflection, $instance, $dependencies)->unwrap($error);
            if ($error) {
                return error($error);
            }
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
