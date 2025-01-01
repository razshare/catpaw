<?php
namespace CatPaw\Web;

use CatPaw\Core\ContainerContext;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Web\Attributes\Consumes;
use CatPaw\Web\Attributes\IgnoreDescribe;
use CatPaw\Web\Attributes\IgnoreOpenApi;
use CatPaw\Web\Attributes\Produces;
use CatPaw\Web\Attributes\Tag;
use CatPaw\Web\Interfaces\OnRequestInterface;
use CatPaw\Web\Interfaces\OnResponseInterface;
use ReflectionFunction;

readonly class Route {
    // @phpstan-ignore-next-line
    public ContainerContext $containerDependencies;

    /**
     *
     * @param ReflectionFunction         $reflectionFunction
     * @param string                     $symbolicMethod
     * @param string                     $symbolicPath
     * @param string                     $workDirectory
     * @param mixed                      $function
     * @param array<Consumes>            $consumes
     * @param array<Produces>            $produces
     * @param array<OnRequestInterface>  $onRequest
     * @param array<OnResponseInterface> $onResponse
     * @param array<AttributeInterface>  $onMount
     * @param array<Tag>                 $tags
     * @param false|IgnoreOpenApi        $ignoreOpenApi
     * @param false|IgnoreDescribe       $ignoreDescribe
     */
    public function __construct(
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
    public function withContainerDependencies(ContainerContext $containerDependencies):void {
        // @phpstan-ignore-next-line
        $this->containerDependencies = $containerDependencies;
    }
}
