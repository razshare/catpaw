<?php

namespace CatPaw\Web\Services;

use CatPaw\Core\Attributes\Service;

#[Service]
class OpenApiService {
    public function __construct(
        private OpenApiStateService $openApiStateService
    ) {
    }

    /**
     * Get the current OpenAPI data.
     * You can safely expose this through a rest api.
     * @return array<mixed>
     */
    public function data():array {
        return $this->openApiStateService->json;
    }

    public function withTitle(string $title):void {
        $this->openApiStateService->json['info']['title'] = $title;
    }

    public function withVersion(string $title):void {
        $this->openApiStateService['info']['version'] = $title;
    }
}
