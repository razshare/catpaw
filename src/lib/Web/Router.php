<?php
namespace CatPaw\Web;

use CatPaw\Core\Container;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;

use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Unsafe;
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
use CatPaw\Web\Attributes\Query;
use CatPaw\Web\Attributes\Summary;
use CatPaw\Web\Attributes\Tag;
use CatPaw\Web\Interfaces\OnRequest;
use CatPaw\Web\Interfaces\OnResult;
use CatPaw\Web\Services\OpenApiService;

use Closure;

use function implode;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

readonly class Router {
    public static function create():self {
        return new self(RouterContext::create());
    }

    private function __construct(private RouterContext $context) {
    }

    /**
     * Initialize a new route.
     * @param  string           $symbolicMethod
     * @param  string           $symbolicPath
     * @param  callable|Closure $function
     * @param  string           $workDirectory
     * @return Unsafe<void>
     */
    public function initialize(
        string $symbolicMethod,
        string $symbolicPath,
        callable|Closure $function,
        string $workDirectory = '',
    ):Unsafe {
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

                if ($successType !== $returnType && $errorType !== $errorType) {
                    return error("All route handlers must return either `{$successType}` or `{$errorType}`, but route `$key` returns `$returnType`.");
                }
            }

            $consumes = Consumes::findAllByFunction($reflectionFunction)->try($error);
            if ($error) {
                return error($error);
            }
            $consumes = $consumes?:[];

            $producesBase = Produces::findAllByFunction($reflectionFunction)->try($error);
            if ($error) {
                return error($error);
            }
            $producesBase = $producesBase?:[];

            $producesItem = ProducesItem::findAllByFunction($reflectionFunction)->try($error);
            if ($error) {
                return error($error);
            }
            $producesItem = $producesItem?:[];

            $producesError = ProducesError::findAllByFunction($reflectionFunction)->try($error);
            if ($error) {
                return error($error);
            }
            $producesError = $producesError?:[];

            $producesErrorItem = ProducesErrorItem::findAllByFunction($reflectionFunction)->try($error);
            if ($error) {
                return error($error);
            }
            $producesErrorItem = $producesErrorItem?:[];

            $producesPage = ProducesPage::findAllByFunction($reflectionFunction)->try($error);
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
            $tags = Tag::findAllByFunction($reflectionFunction)->try($error);
            if ($error) {
                return error($error);
            }

            $onRequest = [];
            $onResult  = [];
            $onMount   = [];

            $parameters = $reflectionFunction->getParameters();

            // This will cache the path resolver so that it will be ready for the first request.
            PathResolver::findResolver($symbolicMethod, $symbolicPath, $parameters)->try($error);
            if ($error) {
                return error($error);
            }

            foreach ($reflectionFunction->getAttributes() as $attribute) {
                $attributeName = $attribute->getName();
                if (!method_exists($attributeName, 'findByFunction')) {
                    continue;
                }

                /** @var false|AttributeInterface $attributeInstance */
                $attributeInstance = $attributeName::findByFunction($reflectionFunction)->try($error);

                if ($error) {
                    return error($error);
                }

                if ($attributeInstance instanceof OnRequest) {
                    $onRequest[] = $attributeInstance;
                } else if ($attributeInstance instanceof OnResult) {
                    $onResult[] = $attributeInstance;
                } else if ($attributeInstance instanceof AttributeInterface) {
                    $onMount[] = $attributeInstance;
                }
            }

            $ignoreOpenApi = IgnoreOpenApi::findByFunction($reflectionFunction)->try($error);

            if ($error) {
                return error($error);
            }

            $ignoreDescribe = IgnoreDescribe::findByFunction($reflectionFunction)->try($error);
            if ($error) {
                return error($error);
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
                onResponse          : $onResult,
                onMount           : $onMount,
                ignoreOpenApi     : $ignoreOpenApi,
                ignoreDescribe    : $ignoreDescribe,
                tags              : $tags,
            );

            $options = DependenciesOptions::create(
                key: $key,
                overwrites:[],
                provides: [],
                fallbacks: [],
                defaultArguments: [],
                context: $route,
            );

            $this->context->setRoute($symbolicMethod, $symbolicPath, $route);

            $route->setOptions($options);

            if (!$ignoreOpenApi) {
                $this->registerRouteForOpenApi($route)->try($error);
                if ($error) {
                    return error($error);
                }
            }
        } catch (ReflectionException $e) {
            return error($e);
        }
        return ok();
    }


    /**
     * @return Unsafe<array>
     */
    private function findRouteOpenApiQueries(
        ReflectionFunction $reflection,
        OpenApiService $api,
    ):Unsafe {
        $result = [];
        foreach ($reflection->getParameters() as $paramReflection) {
            /** @var false|Query $queryAttribute */
            $queryAttribute = Query::findByParameter($paramReflection)->try($error);

            if ($error) {
                return error($error);
            }

            if (!$queryAttribute) {
                continue;
            }

            /** @var false|Summary $summaryAttribute */
            $summaryAttribute = Summary::findByParameter($paramReflection)->try($error);
            if ($error) {
                return error($error);
            }

            /** @var array<Example> $exampleAttributes */
            $exampleAttributes = Example::findAllByParameter($paramReflection)->try($error);
            if ($error) {
                return error($error);
            }

            /** @var false|Example $exampleAttribute */
            $exampleAttribute = Example::findByParameter($paramReflection)->try($error);
            if ($error) {
                return error($error);
            }


            $reflectionType = ReflectionTypeManager::unwrap($paramReflection);


            $type = $reflectionType?$reflectionType->getName():'string';

            $type = match ($type) {
                'int'   => 'integer',
                'float' => 'number',
                'bool'  => 'boolean',
                default => $type,
            };

            $schema = ["type" => $type];


            $name     = $queryAttribute->getName();
            $summary  = $summaryAttribute?$summaryAttribute->getValue():'';
            $examples = [];
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples = [
                    ...$examples,
                    ...($exampleAttribute->getValue() ?? []),
                ];
            }

            if ('' === $name) {
                $name = $paramReflection->getName();
            }

            $result = [
                ...$result,
                ...$api->createParameter(
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
     * @return Unsafe<array>
     */
    private function findRouteOpenApiHeaders(
        ReflectionFunction $reflection,
        OpenApiService $api,
    ):Unsafe {
        $result = [];
        foreach ($reflection->getParameters() as $paramReflection) {
            /** @var false|Header $headerAttribute */
            $headerAttribute = Header::findByParameter($paramReflection)->try($error);
            if ($error) {
                return error($error);
            }
            if (!$headerAttribute) {
                continue;
            }

            /** @var false|Summary $summaryAttribute */
            $summaryAttribute = Summary::findByParameter($paramReflection)->try($error);
            if ($error) {
                return error($error);
            }

            /** @var array<Example> $exampleAttributes */
            $exampleAttributes = Example::findAllByParameter($paramReflection)->try($error);
            if ($error) {
                return error($error);
            }

            /** @var false|Example $exampleAttribute */
            $exampleAttribute = Example::findByParameter($paramReflection)->try($error);
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


            $name     = $headerAttribute->getKey();
            $summary  = $summaryAttribute?$summaryAttribute->getValue():'';
            $examples = [];
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples = [
                    ...$examples,
                    ...($exampleAttribute->getValue() ?? []),
                ];
            }

            if ('' === $name) {
                $name = $paramReflection->getName();
            }

            $result = [
                ...$result,
                ...$api->createParameter(
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
     * @param  ReflectionFunction $reflectionFunction
     * @param  string             $path
     * @param  OpenApiService     $oa
     * @return Unsafe<array>
     */
    private function findRouteOpenApiPathParameters(
        ReflectionFunction $reflectionFunction,
        string $path,
        OpenApiService $oa,
    ):Unsafe {
        $parametersReflections = $reflectionFunction->getParameters();
        /** @var PathResolver $configurations */
        $configurations = PathResolver::findMatchingPathConfigurations($path, $parametersReflections)->try($error);
        if ($error) {
            return error($error);
        }

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
            $summaryAttribute = Summary::findByParameter($paramReflection)->try($error);
            if ($error) {
                return error($error);
            }


            /** @var array<Example> $exampleAttributes */
            $exampleAttributes = Example::findAllByParameter($paramReflection)->try($error);
            if ($error) {
                return error($error);
            }

            /** @var false|Example $exampleAttribute */
            $exampleAttribute = Example::findByParameter($paramReflection)->try($error);
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

            $summary  = $summaryAttribute?$summaryAttribute->getValue():'';
            $examples = [];
            foreach ($exampleAttributes as $exampleAttribute) {
                $examples = [
                    ...$examples,
                    ...($exampleAttribute->getValue() ?? []),
                ];
            }

            $result = [
                ...$result,
                ...$oa->createParameter(
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

    private function findRouteOpenApiPageQueries(
        ReflectionFunction $reflectionFunction,
        OpenApiService $oa,
    ):array {
        foreach ($reflectionFunction->getParameters() as $paramReflection) {
            if (!$type = ReflectionTypeManager::unwrap($paramReflection)) {
                continue;
            }

            if ($type->getName() !== Page::class) {
                continue;
            }

            return [
                ...$oa->createParameter(
                    name: "start",
                    in: 'query',
                    description: "Beginning position of the page.",
                    required: false,
                    schema: [
                        "type" => "integer",
                    ],
                    examples: $oa->createExample(
                        title  : 'Beginning of the page',
                        value  : 0,
                        summary: '0',
                    ),
                ),
                ...$oa->createParameter(
                    name: "size",
                    in: 'query',
                    description: "Size of the page, as in: how many elements the page can contain at most.",
                    required: false,
                    schema: [
                        "type" => "integer",
                    ],
                    examples: $oa->createExample(
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
     * @return Unsafe<void>
     */
    private function registerRouteForOpenApi(Route $route):Unsafe {
        $responses = [];
        $requests  = [];

        $consumes           = $route->consumes;
        $produces           = $route->produces;
        $reflectionFunction = $route->reflectionFunction;
        $symbolicMethod     = $route->symbolicMethod;
        $symbolicPath       = $route->symbolicPath;

        /** @var array<string> $tags */
        $tags = [];

        /** @var Tag $tag */
        foreach ($route->tags as $tag) {
            $tags[] = $tag->getValue();
        }

        if ($consumes) {
            /** @var array<Consumes> $consumes */
            foreach ($consumes as $consumesLocal) {
                foreach ($consumesLocal->getRequest() as $request) {
                    foreach ($request->getValue() as $contentType => $content) {
                        $requests[$contentType] = $content;
                    }
                }
            }
        }

        if ($produces) {
            /** @var array<Produces> $produces */
            foreach ($produces as $producesLocal) {
                foreach ($producesLocal->getResponse() as $response) {
                    foreach ($response->getValue() as $status => $value) {
                        if ($responses[$status] ?? false) {
                            $content = [
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

        $openApi = Container::create(OpenApiService::class)->try($error);
        if ($error) {
            return error($error);
        }

        $headers = $this->findRouteOpenApiHeaders($reflectionFunction, $openApi)->try($error);
        if ($error) {
            return error($error);
        }

        $queries = $this->findRouteOpenApiQueries($reflectionFunction, $openApi)->try($error);
        if ($error) {
            return error($error);
        }

        try {
            $parameters = $this->findRouteOpenApiPathParameters($reflectionFunction, $symbolicPath, $openApi)->try($error);
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
            ...$this->findRouteOpenApiPageQueries($reflectionFunction, $openApi),
        ];


        /** @var false|Summary $summary */
        $summary = Summary::findByFunction($reflectionFunction)->try($error);
        if ($error) {
            return error($error);
        }
        $summary = $summary?:new Summary('');

        $crequests = count($requests);

        /** @var false|OperationId $operationId */
        $operationId = OperationId::findByFunction($reflectionFunction)->try($error);
        if ($error) {
            return error($error);
        }

        if ($operationId) {
            $operationIdValue = $operationId->getValue();
        } else {
            $operationIdValue = \sha1("$symbolicMethod:$symbolicPath:".\sha1(\json_encode($parameters)));
        }

        $openApi->setPath(
            path: $symbolicPath,
            pathContent: [
                ...$openApi->createPathContent(
                    tags: $tags,
                    method     : $symbolicMethod,
                    operationId: $operationIdValue,
                    summary    : $summary->getValue(),
                    parameters : $parameters,
                    requestBody: $openApi->createRequestBody(
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
     * @param  ReflectionMethod $reflection_method
     * @return array
     */
    public function getMappedParameters(ReflectionMethod $reflection_method): array {
        $reflectionParameters = $reflection_method->getParameters();
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
    ): false|Route {
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
    public function routeExists(string $symbolicMethod, string $symbolicPath): bool {
        return $this->context->routeExists($symbolicMethod, $symbolicPath);
    }

    /**
     * Find routes of a method.
     * @param  string       $symbolicMethod
     * @return array<Route>
     */
    public function findRoutesByMethod(string $symbolicMethod): array {
        return $this->context->findRoutesByMethod($symbolicMethod);
    }

    /**
     * Define an alias for an already existing web server path name.
     * @param  string       $originalSymbolicMethod http method of the 2 parameters.
     * @param  string       $originalSymbolicPath   path name to capture.
     * @param  string       $aliasSymbolicPath      alias path name.
     * @return Unsafe<void>
     */
    public function alias(string $originalSymbolicMethod, string $originalSymbolicPath, string $aliasSymbolicPath):Unsafe {
        if ($this->context->routeExists($originalSymbolicMethod, $originalSymbolicPath)) {
            $originalRoute = $this->context->findRoute($originalSymbolicMethod, $originalSymbolicPath);
            $this->custom($originalSymbolicMethod, $aliasSymbolicPath, $originalRoute->function);
        } else {
            return error("Trying to create alias \"$aliasSymbolicPath\" => \"$originalSymbolicPath\", but the original route \"$originalSymbolicPath\" has not been defined.\n");
        }
        return ok();
    }

    /**
     * Define an event callback for a custom http method.
     * @param  string           $method   the name of the http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function custom(string $method, string $path, callable|Closure $function):Unsafe {
        return $this->initialize($method, $path, $function);
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function copy(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('COPY', $path, $function);
    }

    /**
     * Define an event callback for the "DELETE" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function delete(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('DELETE', $path, $function);
    }

    /**
     * Define an event callback for the "GET" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function get(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('GET', $path, $function);
    }

    /**
     * Define an event callback for the "HEAD" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function head(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('HEAD', $path, $function);
    }

    /**
     * Define an event callback for the "LINK" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function link(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('LINK', $path, $function);
    }

    /**
     * Define an event callback for the "LOCK" http method.
     *
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function lock(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('LOCK', $path, $function);
    }

    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function options(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('OPTIONS', $path, $function);
    }

    /**
     * Define an event callback for the "PATCH" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function patch(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('PATCH', $path, $function);
    }

    /**
     * Define an event callback for the "POST" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function post(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('POST', $path, $function);
    }

    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function propfind(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('PROPFIND', $path, $function);
    }

    /**
     * Define an event callback for the "PURGE" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function purge(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('PURGE', $path, $function);
    }

    /**
     * Define an event callback for the "PUT" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function put(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('PUT', $path, $function);
    }

    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function unknown(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('UNKNOWN', $path, $function);
    }

    /**
     * Define an event callback for the "UNLINK" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function unlink(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('UNLINK', $path, $function);
    }

    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function unlock(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('UNLOCK', $path, $function);
    }

    /**
     * Define an event callback for the "VIEW" http method.
     * @param  string           $path     the path the event should listen to.
     * @param  callable|Closure $function the callback to execute.
     * @return Unsafe<void>
     */
    public function view(string $path, callable|Closure $function):Unsafe {
        return $this->initialize('VIEW', $path, $function);
    }
}
