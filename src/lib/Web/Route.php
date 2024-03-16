<?php
namespace CatPaw\Web;

use CatPaw\Core\DependenciesOptions;
use CatPaw\Web\Attributes\Consumes;
use CatPaw\Web\Attributes\IgnoreDescribe;
use CatPaw\Web\Attributes\IgnoreOpenApi;
use CatPaw\Web\Attributes\Produces;
use CatPaw\Web\Attributes\Tag;
use CatPaw\Web\Interfaces\OnRequest;
use CatPaw\Web\Interfaces\OnResult;
use ReflectionFunction;

readonly class Route {
    public DependenciesOptions $options;

    /**
     * @param ReflectionFunction   $reflectionFunction
     * @param string               $workDirectory
     * @param string               $symbolicMethod
     * @param string               $symbolicPath
     * @param callable             $function
     * @param array<Consumes>      $consumes
     * @param array<Produces>      $produces
     * @param array<OnRequest>     $onRequest
     * @param array<OnResult>      $onResponse
     * @param array<OnMount>       $onMount
     * @param array<Tag>           $tags
     * @param false|IgnoreOpenApi  $ignoreOpenApi
     * @param false|IgnoreDescribe $ignoreDescribe
     */
    public static function create(
        ReflectionFunction $reflectionFunction,
        string $workDirectory,
        string $symbolicMethod,
        string $symbolicPath,
        mixed $function,
        array $consumes,
        array $produces,
        array $onRequest,
        array $onResponse,
        array $onMount,
        array $tags,
        false|IgnoreOpenApi $ignoreOpenApi,
        false|IgnoreDescribe $ignoreDescribe,
    ):self {
        return new self(
            reflectionFunction: $reflectionFunction,
            symbolicMethod: $symbolicMethod,
            symbolicPath: $symbolicPath,
            workDirectory: $workDirectory,
            function: $function,
            consumes: $consumes,
            produces: $produces,
            onRequest: $onRequest,
            onResponse: $onResponse,
            onMount: $onMount,
            tags: $tags,
            ignoreOpenApi: $ignoreOpenApi,
            ignoreDescribe: $ignoreDescribe,
        );
    }

    /**
     *
     * @param ReflectionFunction   $reflectionFunction
     * @param string               $symbolicMethod
     * @param string               $symbolicPath
     * @param string               $workDirectory
     * @param mixed                $function
     * @param array                $consumes
     * @param array                $produces
     * @param array                $onRequest
     * @param array                $onResponse
     * @param array                $onMount
     * @param array                $tags
     * @param false|IgnoreOpenApi  $ignoreOpenApi
     * @param false|IgnoreDescribe $ignoreDescribe
     */
    private function __construct(
        public ReflectionFunction $reflectionFunction,
        public string $symbolicMethod,
        public string $symbolicPath,
        public string $workDirectory,
        public mixed $function,
        public array $consumes,
        public array $produces,
        public array $onRequest,
        public array $onResponse,
        public array $onMount,
        public array $tags,
        public false|IgnoreOpenApi $ignoreOpenApi,
        public false|IgnoreDescribe $ignoreDescribe,
    ) {
    }
    public function setOptions(DependenciesOptions $options):void {
        $this->options = $options;
    }
}
