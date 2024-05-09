<?php
use const CatPaw\Web\APPLICATION_JSON;
use CatPaw\Web\Services\OpenApiService;
use function CatPaw\Web\success;

return static fn (OpenApiService $api) => success($api->data())->as(APPLICATION_JSON);
