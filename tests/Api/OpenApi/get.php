<?php
use const CatPaw\Web\APPLICATION_JSON;

use CatPaw\Web\Interfaces\OpenApiInterface;
use function CatPaw\Web\success;

return static fn (OpenApiInterface $api) => success($api->data())->as(APPLICATION_JSON);
