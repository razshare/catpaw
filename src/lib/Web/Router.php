<?php
namespace CatPaw\Web;

use CatPaw\Container;
use CatPaw\DependenciesOptions;
use function CatPaw\error;
use CatPaw\Interfaces\AttributeInterface;

use function CatPaw\ok;
use CatPaw\ReflectionTypeManager;
use CatPaw\Unsafe;
use CatPaw\Web\Attributes\Consumes;
use CatPaw\Web\Attributes\Example;
use CatPaw\Web\Attributes\Header;
use CatPaw\Web\Attributes\IgnoreDescribe;
use CatPaw\Web\Attributes\IgnoreOpenApi;
use CatPaw\Web\Attributes\Produces;
use CatPaw\Web\Attributes\Query;
use CatPaw\Web\Attributes\Summary;
use CatPaw\Web\Interfaces\OnRequest;
use CatPaw\Web\Interfaces\OnResult;
use CatPaw\Web\Services\OpenApiService;

use Closure;

use function implode;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

class Router {
    public static function create():self {
        return new self(RouterContext::create());
    }

    private function __construct(private RouterContext $context) {
    }
    

    /**
     * Initialize a new route.
     * @param string $symbolicMethod
     * @param string $symbolicPath
     * @param string $workDirectory
     * @param  callable|Closure| $callback
     * @return Unsafe<void>
     */
    public function initialize(
        string $symbolicMethod,
        string $symbolicPath,
        callable|Closure $callback,
        string $workDirectory = '',
    ):Unsafe {
        try {
            if (!str_starts_with($symbolicPath, '/')) {
                return error("Symbolic paths must start with `/`, received `$symbolicPath`.");
            }

            if (!$callback instanceof Closure) {
                $callback = Closure::fromCallable($callback);
            }

            try {
                $reflectionFunction = new ReflectionFunction($callback);
            } catch(Throwable $e) {
                return error($e);
            }

            $consumesAttempt = Consumes::findByFunction($reflectionFunction);
            
            if ($consumesAttempt->error) {
                return error($consumesAttempt->error);
            }
            $consumes = $consumesAttempt->value?:new Consumes();

            $producesAttempt = Produces::findByFunction($reflectionFunction);
            if ($producesAttempt->error) {
                return error($producesAttempt->error);
            }
            $produces = $producesAttempt->value?:new Produces();

            $onRequest = [];
            $onResult  = [];
            $onMount   = [];

            $parameters = $reflectionFunction->getParameters();

            // This will cache the path resolver so that it will be ready for the first request.
            if ($error = PathResolver::findResolver($symbolicMethod, $symbolicPath, $parameters)->error) {
                return error($error);
            }
            
            foreach ($reflectionFunction->getAttributes() as $attribute) {
                $aname = $attribute->getName();
                if (!method_exists($aname, 'findByFunction')) {
                    continue;
                }

                /** @var Unsafe<false|self> */
                $attributeInstance = $aname::findByFunction($reflectionFunction);

                if ($attributeInstance->error) {
                    return error($attributeInstance->error);
                }
     
                if ($attributeInstance instanceof OnRequest) {
                    $onRequest[] = $attributeInstance;
                } else if ($attributeInstance instanceof OnResult) {
                    $onResult[] = $attributeInstance;
                } else if ($attributeInstance instanceof AttributeInterface) {
                    $onMount[] = $attributeInstance;
                }
            }

            $ignoreOpenApiAttempt = IgnoreOpenApi::findByFunction($reflectionFunction);
            
            if ($ignoreOpenApiAttempt->error) {
                return error($ignoreOpenApiAttempt->error);
            }

            $ignoreOpenApi = $ignoreOpenApiAttempt->value;

            $ignoreDescribeAttempt = IgnoreDescribe::findByFunction($reflectionFunction);
            if ($ignoreDescribeAttempt->error) {
                return error($ignoreDescribeAttempt->error);
            }

            $ignoreDescribe = $ignoreDescribeAttempt->value;

            $route = Route::create(
                reflectionFunction: $reflectionFunction,
                symbolicMethod: $symbolicMethod,
                symbolicPath: $symbolicPath,
                workDirectory: $workDirectory,
                callback: $callback,
                consumes: $consumes,
                produces: $produces,
                onRequest: $onRequest,
                onResult: $onRequest,
                onMount: $onMount,
                ignoreOpenApi: $ignoreOpenApi,
                ignoreDescribe: $ignoreDescribe,
            );

            $options = DependenciesOptions::create(
                key: '',
                overwrites:[],
                provides: [],
                fallbacks: [],
                defaultArguments: [],
                context: $route,
            );

            $this->context->setRoute($symbolicMethod, $symbolicPath, $route);

            $route->setOptions($options);

            if (!$ignoreOpenApi) {
                $this->registerRouteForOpenApi($route);
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
            /** @var Unsafe<Query> */
            $queryAttribute = Query::findByParameter($paramReflection);

            if ($queryAttribute->error) {
                return error($queryAttribute->error);
            }

            if (!$queryAttribute->value) {
                continue;
            }

            /** @var Unsafe<Summary> */
            $summaryAttribute = Summary::findByParameter($paramReflection);
            if ($summaryAttribute->error) {
                return error($summaryAttribute->error);
            }

            /** @var Unsafe<Example> */
            $exampleAttribute = Example::findByParameter($paramReflection);
            if ($exampleAttribute->error) {
                return error($exampleAttribute->error);
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
            

            $name    = $queryAttribute->value?$queryAttribute->value->getName():'';
            $summary = $summaryAttribute->value?$summaryAttribute->value->getValue():'';
            $example = $exampleAttribute->value?$exampleAttribute->value->getValue():[];
            
            
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
                    examples: $example,
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
            /** @var Unsafe<Header> */
            $headerAttribute = Header::findByParameter($paramReflection);
            if ($headerAttribute->error) {
                return error($headerAttribute->error);
            }
            if (!$headerAttribute->value) {
                continue;
            }

            /** @var Unsafe<Summary> */
            $summaryAttribute = Summary::findByParameter($paramReflection);
            if ($summaryAttribute->error) {
                return error($summaryAttribute->error);
            }


            /** @var Unsafe<Example> */
            $exampleAttribute = Example::findByParameter($paramReflection);
            if ($exampleAttribute->error) {
                return error($exampleAttribute->error);
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
            

            $name    = $headerAttribute?$headerAttribute->value->getKey():'';
            $summary = $summaryAttribute?$summaryAttribute->value->getValue():'';
            $example = $exampleAttribute?$exampleAttribute->value->getValue():[];
            
            
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
                    examples: $example,
                ),
            ];
        }

        return ok($result);
    }

    /**
     * 
     * @param  ReflectionFunction  $reflectionFunction
     * @param  string              $path
     * @param  OpenApiService      $oa
     * @throws ReflectionException
     * @return Unsafe<array>
     */
    private function findRouteOpenApiPathParameters(
        ReflectionFunction $reflectionFunction,
        string $path,
        OpenApiService $oa,
    ):Unsafe {
        $parametersReflections = $reflectionFunction->getParameters();
        /** @var Unsafe<PathResolver> */
        $configurations = PathResolver::findMatchingPathConfigurations($path, $parametersReflections);
        if ($configurations->error) {
            return error($configurations->error);
        }

        $result = [];


        foreach ($configurations->value as $configuration) {
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

            $summary = '';
            /** @var array{type:string} */
            $schema  = ["type" => "string"];
            $example = [];

            $reflectionType = ReflectionTypeManager::unwrap($paramReflection);

            /** @var Unsafe<Summary> */
            $summaryAttribute = Summary::findByParameter($paramReflection);
            if ($summaryAttribute->error) {
                return error($summaryAttribute->error);
            }


            /** @var Unsafe<Example> */
            $exampleAttribute = Example::findByParameter($paramReflection);
            if ($exampleAttribute->error) {
                return error($exampleAttribute->error);
            }

            $type = $reflectionType?$reflectionType->getName():'string';
            $type = match ($type) {
                'int'   => 'integer',
                'float' => 'number',
                'bool'  => 'boolean',
                default => $type,
            };
            

            $schema = ["type" => $type];
            
            $summary = $summaryAttribute->value?$summaryAttribute->value->getValue():'';
            $example = $exampleAttribute->value?$exampleAttribute->value->getValue():[];
            
            $result = [
                ...$result,
                ...$oa->createParameter(
                    name: $name,
                    in: 'path',
                    description: $summary,
                    required: true,
                    schema: $schema,
                    examples: $example,
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
                        title: 'Beginning of the page',
                        summary: '0',
                        value: 0,
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
                        title: 'Size of the page',
                        summary: '3',
                        value: 3,
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

        if ($consumes) {
            foreach ($consumes->getRequest() as $request) {
                foreach ($request->getValue() as $status => $content) {
                    $requests[$status] = $content;
                }
            }
        }

        if ($produces) {
            foreach ($produces->getResponse() as $response) {
                foreach ($response->getValue() as $status => $response) {
                    if ($responses[$status] ?? false) {
                        $content = [
                            ...($responses[$status]['content'] ?? []),
                            ...$response['content'] ?? [],
                        ];
                    } else {
                        $content = $response['content'] ?? [];
                    }
    
                    $responses[$status] = [
                        "description" => "",
                        "content"     => $content,
                    ];
                }
            }
        }
        /** @var Unsafe<OpenApiService> */
        $openApiAttempt = Container::create(OpenApiService::class);
        if ($openApiAttempt->error) {
            return error($openApiAttempt->error);
        }

        $openApi = $openApiAttempt->value;

        $headers = $this->findRouteOpenApiHeaders($reflectionFunction, $openApi);
        if ($headers->error) {
            return error($headers->error);
        }

        $queries = $this->findRouteOpenApiQueries($reflectionFunction, $openApi);
        if ($queries->error) {
            return error($queries->error);
        }

        $parameters = $this->findRouteOpenApiPathParameters($reflectionFunction, $symbolicPath, $openApi, );
        if ($parameters->error) {
            return error($parameters->error);
        }


        $parameters = [
            ... $headers->value,
            ...$queries->value,
            ...$parameters->value,
            ...$this->findRouteOpenApiPageQueries($reflectionFunction, $openApi),
        ];


        /** @var Unsafe<false|Summary> */
        $summaryAttempt = Summary::findByFunction($reflectionFunction);
        if ($summaryAttempt->error) {
            return error($summaryAttempt->error);
        }
        $summary = $summaryAttempt->value?: new Summary('');

        $crequests = count($requests);

        $openApi->setPath(
            path: $symbolicPath,
            pathContent: [
                ...$openApi->createPathContent(
                    method: $symbolicMethod,
                    operationID: \sha1("$symbolicMethod:$symbolicPath:".\sha1(\json_encode($parameters))),
                    summary: $summary->getValue(),
                    parameters: $parameters,
                    responses: $responses,
                    requestBody: $openApi->createRequestBody(
                        description: $crequests > 0?'This is the body of the request':'',
                        required: $crequests    > 0,
                        content: $requests,
                    ),
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
            $this->custom($originalSymbolicMethod, $aliasSymbolicPath, $originalRoute->callback);
        } else {
            return error("Trying to create alias \"$aliasSymbolicPath\" => \"$originalSymbolicPath\", but the original route \"$originalSymbolicPath\" has not beed defined.\n");
        }
        // if (isset($this->context->routes[$method][$original])) {
        //     $this->custom($method, $alias, $this->context->routes[$method][$original]);
        // }
    }

    /**
     * Define an event callback for a custom http method.
     * @param  string                                   $method   the name of the http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function custom(string $method, string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize($method, $path, $callback);
    }

    /**
     * Define an event callback for the "COPY" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function copy(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('COPY', $path, $callback);
    }

    /**
     * Define an event callback for the "DELETE" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function delete(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('DELETE', $path, $callback);
    }

    /**
     * Define an event callback for the "GET" http method.
     * @param  string                 $path     the path the event should listen to.
     * @param  array|callable|Closure $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function get(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('GET', $path, $callback);
    }

    /**
     * Define an event callback for the "HEAD" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function head(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('HEAD', $path, $callback);
    }

    /**
     * Define an event callback for the "LINK" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function link(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('LINK', $path, $callback);
    }

    /**
     * Define an event callback for the "LOCK" http method.
     *
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function lock(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('LOCK', $path, $callback);
    }

    /**
     * Define an event callback for the "OPTIONS" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function options(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('OPTIONS', $path, $callback);
    }

    /**
     * Define an event callback for the "PATCH" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function patch(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('PATCH', $path, $callback);
    }

    /**
     * Define an event callback for the "POST" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function post(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('POST', $path, $callback);
    }

    /**
     * Define an event callback for the "PROPFIND" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function propfind(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('PROPFIND', $path, $callback);
    }

    /**
     * Define an event callback for the "PURGE" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function purge(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('PURGE', $path, $callback);
    }

    /**
     * Define an event callback for the "PUT" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function put(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('PUT', $path, $callback);
    }

    /**
     * Define an event callback for the "UNKNOWN" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function unknown(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('UNKNOWN', $path, $callback);
    }

    /**
     * Define an event callback for the "UNLINK" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function unlink(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('UNLINK', $path, $callback);
    }

    /**
     * Define an event callback for the "UNLOCK" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function unlock(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('UNLOCK', $path, $callback);
    }

    /**
     * Define an event callback for the "VIEW" http method.
     * @param  string                                   $path     the path the event should listen to.
     * @param  callable|Closure|array<callable|Closure> $callback the callback to execute.
     * @return Unsafe<void>
     */
    public function view(string $path, array|callable|Closure $callback):Unsafe {
        return $this->initialize('VIEW', $path, $callback);
    }
}