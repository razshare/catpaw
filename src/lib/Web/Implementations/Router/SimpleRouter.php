<?php
namespace CatPaw\Web\Implementations\Router;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;
use CatPaw\Web\Attributes\Consumes;
use CatPaw\Web\Attributes\Example;
use CatPaw\Web\Attributes\Header;
use CatPaw\Web\Attributes\IgnoreDescribe;
use CatPaw\Web\Attributes\IgnoreOpenApi;
use CatPaw\Web\Attributes\OperationId;
use CatPaw\Web\Attributes\Produces;
use CatPaw\Web\Attributes\ProducesError;
use CatPaw\Web\Attributes\ProducesErrorItem;
use CatPaw\Web\Attributes\ProducesItem;
use CatPaw\Web\Attributes\ProducesPage;
use CatPaw\Web\Attributes\Summary;
use CatPaw\Web\Attributes\Tag;
use CatPaw\Web\ErrorResponseModifier;
use CatPaw\Web\Interfaces\OnRequestInterface;
use CatPaw\Web\Interfaces\OnResponseInterface;
use CatPaw\Web\Interfaces\OpenApiStateInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Page;
use CatPaw\Web\PathResolver;
use CatPaw\Web\Route;
use CatPaw\Web\RouterContext;
use CatPaw\Web\SuccessResponseModifier;
use Closure;
use function implode;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

#[Provider]
class SimpleRouter implements RouterInterface {
    public readonly RouterContext $context;
    public function __construct(
        public readonly OpenApiStateInterface $openApiState,
    ) {
        $this->context = RouterContext::create();
    }

    public function getContext():RouterContext {
        return $this->context;
    }

    /**
     * Initialize a new route.
     * @param  string                  $symbolicMethod
     * @param  string                  $symbolicPath
     * @param  callable|Closure        $function
     * @param  string                  $workDirectory
     * @return Result<RouterInterface>
     */
    public function initialize(
        string $symbolicMethod,
        string $symbolicPath,
        callable|Closure $function,
        string $workDirectory = '',
    ):Result {
        try {
            if (!str_starts_with($symbolicPath, '/')) {
                return error("Symbolic paths must start with `/`, received `$symbolicPath`.");
            }

            $key = "$symbolicMethod:$symbolicPath";

            if (!$function instanceof Closure) {
                $function = Closure::fromCallable($function);
            }

            try {
                $reflectionFunction = new ReflectionFunction($function);
            } catch(Throwable $e) {
                return error($e);
            }

            if ($reflectionFunction->hasReturnType()) {
                $successType = SuccessResponseModifier::class;
                $errorType   = ErrorResponseModifier::class;
                $returnType  = $reflectionFunction->getReturnType();

                // @phpstan-ignore-next-line
                if ($successType !== $returnType && $errorType !== $errorType) {
                    return error("All route handlers must return either `{$successType}` or `{$errorType}`, but route `$key` returns `$returnType`.");
                }
            }

            $consumes = Consumes::findAllByFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $consumes = $consumes?:[];

            $producesBase = Produces::findAllByFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $producesBase = $producesBase?:[];

            $producesItem = ProducesItem::findAllByFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $producesItem = $producesItem?:[];

            $producesError = ProducesError::findAllByFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $producesError = $producesError?:[];

            $producesErrorItem = ProducesErrorItem::findAllByFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $producesErrorItem = $producesErrorItem?:[];

            $producesPage = ProducesPage::findAllByFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $producesPage = $producesPage?:[];

            $produces = [
                ...$producesBase,
                ...$producesItem,
                ...$producesError,
                ...$producesErrorItem,
                ...$producesPage,
            ];

            /** @var array<Tag> $tags */
            $tags = Tag::findAllByFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }

            $onRequest  = [];
            $onResponse = [];
            $onMount    = [];
            $parameters = $reflectionFunction->getParameters();

            // This will cache the path resolver so that it will be ready for the first request.
            PathResolver::findResolver($symbolicMethod, $symbolicPath, $parameters)->unwrap($error);
            if ($error) {
                return error($error);
            }

            foreach ($reflectionFunction->getAttributes() as $attribute) {
                $attributeName = $attribute->getName();
                if (!method_exists($attributeName, 'findByFunction')) {
                    continue;
                }

                /** @var false|AttributeInterface $attributeInstance */

                $attributeInstance = $attributeName::findByFunction($reflectionFunction)->unwrap($error);
                // @phpstan-ignore-next-line
                if ($error) {
                    return error($error);
                }

                if ($attributeInstance instanceof OnRequestInterface) {
                    $onRequest[] = $attributeInstance;
                }
                
                if ($attributeInstance instanceof OnResponseInterface) {
                    $onResponse[] = $attributeInstance;
                }

                if ($attributeInstance instanceof AttributeInterface) {
                    $onMount[] = $attributeInstance;
                }
            }

            $ignoreOpenApi = IgnoreOpenApi::findByFunction($reflectionFunction)->unwrap($error);

            if ($error) {
                return error($error);
            }

            $ignoreDescribe = IgnoreDescribe::findByFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }


            $reflectionParameters = $reflectionFunction->getParameters();
            $configurations       = PathResolver::findMatchingPathConfigurations($symbolicPath, $reflectionParameters)->unwrap($error);

            $names = [];
            foreach ($configurations as $configuration) {
                $names[] = $configuration->name;
            }

            foreach ($reflectionParameters as $paramReflection) {
                $type = ReflectionTypeManager::unwrap($paramReflection)->getName();
                if ($error) {
                    return error($error);
                }
                
                if ('string' !== $type && 'int' !== $type && 'bool' !== $type && 'float' !== $type) {
                    continue;
                }
    
                if (in_array($paramReflection->getName(), $names)) {
                    continue;
                }

                $wrapper = ReflectionTypeManager::wrap($paramReflection);

                if (!$wrapper->allowsNullValue() && !$wrapper->allowsDefaultValue()) {
                    return error("All query parameters must define a default value or at least be nullable, received query parameter `{$paramReflection->getName()}` in `$symbolicMethod $symbolicPath` which doesn't have a default value nor is nullable.");
                }
            }

            $route = Route::create(
                reflectionFunction: $reflectionFunction,
                workDirectory     : $workDirectory,
                symbolicMethod    : $symbolicMethod,
                symbolicPath      : $symbolicPath,
                function          : $function,
                consumes          : $consumes,
                produces          : $produces,
                onRequest         : $onRequest,
                onResponse        : $onResponse,
                onMount           : $onMount,
                ignoreOpenApi     : $ignoreOpenApi,
                ignoreDescribe    : $ignoreDescribe,
                tags              : $tags,
            );

            $options = new DependenciesOptions(
                key: $key,
                overwrites:[],
                provides: [],
                fallbacks: [],
                defaultArguments: [],
                context: $route,
            );

            $this->context->addRoute($symbolicMethod, $symbolicPath, $route);

            $route->withOptions($options);

            if (!$ignoreOpenApi) {
                $this->registerRouteForOpenApi($route)->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
        } catch (ReflectionException $e) {
            return error($e);
        }
        // @phpstan-ignore-next-line
        return ok($this);
    }


    /**
     * @return Result<array<mixed>>
     */
    private function findRouteOpenApiQueries(
        ReflectionFunction $reflectionFunction,
        string $symbolicPath
    ):Result {
        /** @var array<mixed> */
        $result               = [];
        $reflectionParameters = $reflectionFunction->getParameters();
        $configurations       = PathResolver::findMatchingPathConfigurations($symbolicPath, $reflectionParameters)->unwrap($error);

        $names = [];
        foreach ($configurations as $configuration) {
            $names[] = $configuration->name;
        }

        foreach ($reflectionParameters as $paramReflection) {
            $type = ReflectionTypeManager::unwrap($paramReflection)->getName();
            if ($error) {
                return error($error);
            }
            
            if ('string' !== $type && 'int' !== $type && 'bool' !== $type && 'float' !== $type) {
                continue;
            }

            if (in_array($paramReflection->getName(), $names)) {
                continue;
            }

            /**
             * @var false|Summary $summaryAttribute 
             */
            $summaryAttribute = Summary::findByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }

            /** @var array<Example> $exampleAttributes */
            $exampleAttributes = Example::findAllByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }

            /** @var false|Example $exampleAttribute */
            $exampleAttribute = Example::findByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }

            $reflectionType = ReflectionTypeManager::unwrap($paramReflection);
            $type           = $reflectionType?$reflectionType->getName():'string';
            $type           = match ($type) {
                'int'   => 'integer',
                'float' => 'number',
                'bool'  => 'boolean',
                default => $type,
            };

            $schema   = ["type" => $type];
            $name     = $paramReflection->getName();
            $summary  = $summaryAttribute?$summaryAttribute->value():'';
            $examples = [];
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples = [
                    ...$examples,
                    ...($exampleAttribute->value() ?? []),
                ];
            }

            $result = [
                ...$result,
                ...$this->openApiState->createParameter(
                    name: $name,
                    in: 'query',
                    description: $summary,
                    required: false,
                    schema: $schema,
                    examples: $examples,
                ),
            ];
        }
        return ok($result);
    }

    /**
     * @return Result<array<mixed>>
     */
    private function findRouteOpenApiHeaders(
        ReflectionFunction $reflection,
    ):Result {
        /** @var array<mixed> */
        $result = [];
        foreach ($reflection->getParameters() as $paramReflection) {
            /** @var false|Header $headerAttribute */
            $headerAttribute = Header::findByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }
            if (!$headerAttribute) {
                continue;
            }

            /** @var false|Summary $summaryAttribute */
            $summaryAttribute = Summary::findByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }

            /** @var array<Example> $exampleAttributes */
            $exampleAttributes = Example::findAllByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }

            /** @var false|Example $exampleAttribute */
            $exampleAttribute = Example::findByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }


            $reflectionType = ReflectionTypeManager::unwrap($paramReflection);
            $type           = $reflectionType?$reflectionType->getName():'string';

            $type = match ($type) {
                'int'   => 'integer',
                'float' => 'number',
                'bool'  => 'boolean',
                default => $type,
            };

            $schema = ["type" => $type];


            $name     = $headerAttribute->key();
            $summary  = $summaryAttribute?$summaryAttribute->value():'';
            $examples = [];
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples = [
                    ...$examples,
                    ...($exampleAttribute->value() ?? []),
                ];
            }

            if ('' === $name) {
                $name = $paramReflection->getName();
            }

            $result = [
                ...$result,
                ...$this->openApiState->createParameter(
                    name: $name,
                    in: 'header',
                    description: $summary,
                    required: false,
                    schema: $schema,
                    examples: $examples,
                ),
            ];
        }

        return ok($result);
    }

    /**
     *
     * @param  ReflectionFunction   $reflectionFunction
     * @param  string               $symbolicPath
     * @return Result<array<mixed>>
     */
    private function findRouteOpenApiPathParameters(
        ReflectionFunction $reflectionFunction,
        string $symbolicPath
    ):Result {
        $parametersReflections = $reflectionFunction->getParameters();
        $configurations        = PathResolver::findMatchingPathConfigurations($symbolicPath, $parametersReflections)->unwrap($error);
        if ($error) {
            return error($error);
        }

        /** @var array<mixed> */
        $result = [];


        foreach ($configurations as $configuration) {
            $paramReflection = false;

            $name = $configuration->name;

            foreach ($parametersReflections as $parameterReflectionLocal) {
                $nameLocal = $parameterReflectionLocal->getName();
                if ($nameLocal === $name) {
                    $paramReflection = $parameterReflectionLocal;
                }
            }

            if (!$paramReflection) {
                continue;
            }

            $reflectionType = ReflectionTypeManager::unwrap($paramReflection);

            /** @var false|Summary $summaryAttribute */
            $summaryAttribute = Summary::findByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }


            /** @var array<Example> $exampleAttributes */
            $exampleAttributes = Example::findAllByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }

            /** @var false|Example $exampleAttribute */
            $exampleAttribute = Example::findByParameter($paramReflection)->unwrap($error);
            if ($error) {
                return error($error);
            }

            $type = $reflectionType?$reflectionType->getName():'string';
            $type = match ($type) {
                'int'   => 'integer',
                'float' => 'number',
                'bool'  => 'boolean',
                default => $type,
            };


            $schema = ["type" => $type];

            $summary  = $summaryAttribute?$summaryAttribute->value():'';
            $examples = [];
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples = [
                    ...$examples,
                    ...($exampleAttribute->value() ?? []),
                ];
            }

            $result = [
                ...$result,
                ...$this->openApiState->createParameter(
                    name: $name,
                    in: 'path',
                    description: $summary,
                    required: true,
                    schema: $schema,
                    examples: $examples,
                ),
            ];
        }
        return ok($result);
    }

    /**
     *
     * @param  ReflectionFunction $reflectionFunction
     * @return array<mixed>
     */
    private function findRouteOpenApiPageQueries(
        ReflectionFunction $reflectionFunction,
    ):array {
        foreach ($reflectionFunction->getParameters() as $paramReflection) {
            if (!$type = ReflectionTypeManager::unwrap($paramReflection)) {
                continue;
            }

            if ($type->getName() !== Page::class) {
                continue;
            }

            return [
                ...$this->openApiState->createParameter(
                    name: "start",
                    in: 'query',
                    description: "Beginning position of the page.",
                    required: false,
                    schema: [
                        "type" => "integer",
                    ],
                    examples: $this->openApiState->createExample(
                        title  : 'Beginning of the page',
                        value  : 0,
                        summary: '0',
                    ),
                ),
                ...$this->openApiState->createParameter(
                    name: "size",
                    in: 'query',
                    description: "Size of the page, as in: how many elements the page can contain at most.",
                    required: false,
                    schema: [
                        "type" => "integer",
                    ],
                    examples: $this->openApiState->createExample(
                        title  : 'Size of the page',
                        value  : 3,
                        summary: '3',
                    ),
                ),
            ];
        }

        return [];
    }

    /**
     * @param  Route        $route
     * @return Result<None>
     */
    private function registerRouteForOpenApi(Route $route):Result {
        $responses          = [];
        $requests           = [];
        $consumes           = $route->consumes;
        $produces           = $route->produces;
        $reflectionFunction = $route->reflectionFunction;
        $symbolicMethod     = $route->symbolicMethod;
        $symbolicPath       = $route->symbolicPath;

        /** @var array<string> $tags */
        $tags = [];

        /** @var Tag $tag */
        foreach ($route->tags as $tag) {
            $tags[] = $tag->value();
        }

        if ($consumes) {
            /** @var array<Consumes> $consumes */
            foreach ($consumes as $consumesLocal) {
                foreach ($consumesLocal->request() as $request) {
                    foreach ($request->value() as $contentType => $content) {
                        $requests[$contentType] = $content;
                    }
                }
            }
        }

        if ($produces) {
            foreach ($produces as $producesLocal) {
                foreach ($producesLocal->response() as $response) {
                    foreach ($response->value() as $status => $value) {
                        if ($responses[$status] ?? false) {
                            $content = [
                                // @phpstan-ignore-next-line
                                ...($responses[$status]['content'] ?? []),
                                ...$value['content'] ?? [],
                            ];
                        } else {
                            $content = $value['content'] ?? [];
                        }
                        if (isset($responses[$status])) {
                            $responses[$status] = [
                                "description" => $value['description'] ?? '',
                                "content"     => [
                                    // @phpstan-ignore-next-line
                                    ...$responses[$status]['content'] ?? [],
                                    ...$content,
                                ],
                            ];
                        } else {
                            $responses[$status] = [
                                "description" => $value['description'] ?? '',
                                "content"     => $content,
                            ];
                        }
                    }
                }
            }
        }

        $headers = $this->findRouteOpenApiHeaders($reflectionFunction)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $queries = $this->findRouteOpenApiQueries($reflectionFunction, $symbolicPath)->unwrap($error);
        if ($error) {
            return error($error);
        }

        try {
            $parameters = $this->findRouteOpenApiPathParameters($reflectionFunction, $symbolicPath)->unwrap($error);
        } catch(Throwable $e) {
            return error($e);
        }

        if ($error) {
            return error($error);
        }


        $parameters = [
            ... $headers,
            ...$queries,
            ...$parameters,
            ...$this->findRouteOpenApiPageQueries($reflectionFunction),
        ];


        /** @var false|Summary $summary */
        $summary = Summary::findByFunction($reflectionFunction)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $summary = $summary?:new Summary('');

        $crequests = count($requests);

        /** @var false|OperationId $operationId */
        $operationId = OperationId::findByFunction($reflectionFunction)->unwrap($error);
        if ($error) {
            return error($error);
        }

        if ($operationId) {
            $operationIdValue = $operationId->value();
        } else {
            $operationIdValue = \sha1("$symbolicMethod:$symbolicPath:".\sha1(\json_encode($parameters)));
        }

        $this->openApiState->withPath(
            path: $symbolicPath,
            pathContent: [
                ...$this->openApiState->createPathContent(
                    tags: $tags,
                    method     : $symbolicMethod,
                    operationId: $operationIdValue,
                    summary    : $summary->value(),
                    parameters : $parameters,
                    requestBody: $this->openApiState->createRequestBody(
                        description: $crequests > 0?'This is the body of the request':'',
                        required: $crequests    > 0,
                        content: $requests,
                    ),
                    responses  : $responses,
                ),
            ],
        );

        return ok();
    }

    /**
     * @param  ReflectionMethod     $reflectionMethod
     * @return array{string,string}
     */
    public function mappedParameters(ReflectionMethod $reflectionMethod):array {
        $reflectionParameters = $reflectionMethod->getParameters();
        $namedAndTypedParams  = [];
        $namedParams          = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            if (!$reflectionType = ReflectionTypeManager::unwrap($reflectionParameter)) {
                $type = '';
            } else {
                $type = $reflectionType->getName();
            }

            $name                  = $reflectionParameter->getName();
            $namedAndTypedParams[] = "$type &\$$name";
            $namedParams[]         = "\$$name";
        }
        $namedAndTypedParamsString = implode(',', $namedAndTypedParams);
        $namedParamsString         = implode(',', $namedParams);
        return [$namedAndTypedParamsString, $namedParamsString];
    }

    /**
     * Find a route.
     * @param  string      $symbolicMethod
     * @param  string      $symbolicPath
     * @return false|Route
     */
    public function findRoute(
        string $symbolicMethod,
        string $symbolicPath,
    ):false|Route {
        if (!$this->context->routeExists($symbolicMethod, $symbolicPath)) {
            return false;
        }
        return $this->context->findRoute($symbolicMethod, $symbolicPath);
    }

    /**
     * Check if a route exists.
     * @param  string $symbolicMethod
     * @param  string $symbolicPath
     * @return bool
     */
    public function routeExists(string $symbolicMethod, string $symbolicPath):bool {
        return $this->context->routeExists($symbolicMethod, $symbolicPath);
    }

    /**
     * Find routes of a method.
     * @param  string       $symbolicMethod
     * @return array<Route>
     */
    public function findRoutesByMethod(string $symbolicMethod):array {
        return $this->context->findRoutesByMethod($symbolicMethod);
    }

    /**
     * Define an alias for an already existing web server path name.
     * @param  string       $originalSymbolicMethod http method of the 2 parameters.
     * @param  string       $originalSymbolicPath   path name to capture.
     * @param  string       $aliasSymbolicPath      alias path name.
     * @return Result<None>
     */
    public function alias(string $originalSymbolicMethod, string $originalSymbolicPath, string $aliasSymbolicPath):Result {
        if ($this->context->routeExists($originalSymbolicMethod, $originalSymbolicPath)) {
            $originalRoute = $this->context->findRoute($originalSymbolicMethod, $originalSymbolicPath);
            $this->custom($originalSymbolicMethod, $aliasSymbolicPath, $originalRoute->function);
        } else {
            return error("Trying to create alias \"$aliasSymbolicPath\" => \"$originalSymbolicPath\", but the original route \"$originalSymbolicPath\" has not been defined.");
        }
        return ok();
    }

    /**
     * Define an event callback for a custom http method.
     * @param  string                  $method   the name of the http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function custom(string $method, string $path, callable|Closure $function):Result {
        return $this->initialize($method, $path, $function);
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function copy(string $path, callable|Closure $function):Result {
        return $this->initialize('COPY', $path, $function);
    }

    /**
     * Define an event callback for the "DELETE" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function delete(string $path, callable|Closure $function):Result {
        return $this->initialize('DELETE', $path, $function);
    }

    /**
     * Define an event callback for the "GET" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function get(string $path, callable|Closure $function):Result {
        return $this->initialize('GET', $path, $function);
    }

    /**
     * Define an event callback for the "HEAD" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function head(string $path, callable|Closure $function):Result {
        return $this->initialize('HEAD', $path, $function);
    }

    /**
     * Define an event callback for the "LINK" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function link(string $path, callable|Closure $function):Result {
        return $this->initialize('LINK', $path, $function);
    }

    /**
     * Define an event callback for the "LOCK" http method.
     *
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function lock(string $path, callable|Closure $function):Result {
        return $this->initialize('LOCK', $path, $function);
    }

    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function options(string $path, callable|Closure $function):Result {
        return $this->initialize('OPTIONS', $path, $function);
    }

    /**
     * Define an event callback for the "PATCH" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function patch(string $path, callable|Closure $function):Result {
        return $this->initialize('PATCH', $path, $function);
    }

    /**
     * Define an event callback for the "POST" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function post(string $path, callable|Closure $function):Result {
        return $this->initialize('POST', $path, $function);
    }

    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function propfind(string $path, callable|Closure $function):Result {
        return $this->initialize('PROPFIND', $path, $function);
    }

    /**
     * Define an event callback for the "PURGE" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function purge(string $path, callable|Closure $function):Result {
        return $this->initialize('PURGE', $path, $function);
    }

    /**
     * Define an event callback for the "PUT" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function put(string $path, callable|Closure $function):Result {
        return $this->initialize('PUT', $path, $function);
    }

    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function unknown(string $path, callable|Closure $function):Result {
        return $this->initialize('UNKNOWN', $path, $function);
    }

    /**
     * Define an event callback for the "UNLINK" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function unlink(string $path, callable|Closure $function):Result {
        return $this->initialize('UNLINK', $path, $function);
    }

    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function unlock(string $path, callable|Closure $function):Result {
        return $this->initialize('UNLOCK', $path, $function);
    }

    /**
     * Define an event callback for the "VIEW" http method.
     * @param  string                  $path     the path the event should listen to.
     * @param  callable|Closure        $function the callback to execute.
     * @return Result<RouterInterface>
     */
    public function view(string $path, callable|Closure $function):Result {
        return $this->initialize('VIEW', $path, $function);
    }
}
