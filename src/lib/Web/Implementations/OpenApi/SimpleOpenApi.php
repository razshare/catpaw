<?php

namespace CatPaw\Web\Implementations\OpenApi;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Web\Interfaces\OpenApiInterface;
use CatPaw\Web\Interfaces\OpenApiStateInterface;

#[Provider]
class SimpleOpenApi implements OpenApiInterface {
    /** @var array<mixed> */
    private array $data = [];
    public function __construct(
        public readonly OpenApiStateInterface $openApiState
    ) {
        $this->data = &$openApiState->data();
    }

    /**
     * Get the current OpenAPI data.
     * You can safely expose this through a rest api.
     * @return array<mixed>
     */
    public function data():array {
        return $this->data;
    }

    public function withTitle(string $title):void {
        $this->data['info']['title'] = $title;
    }

    public function withVersion(string $title):void {
        $this->data['info']['version'] = $title;
    }
}
