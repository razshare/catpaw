<?php
use const CatPaw\Web\__APPLICATION_JSON;
use CatPaw\Web\Services\OpenApiService;
use function CatPaw\Web\success;

return static fn (OpenApiService $api) => success($api->getData())->as(__APPLICATION_JSON);
