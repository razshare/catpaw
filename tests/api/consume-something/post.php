<?php
use const CatPaw\Web\APPLICATION_JSON;
use CatPaw\Web\Attributes\Body;
use CatPaw\Web\Attributes\Consumes;
use function CatPaw\Web\success;

class SchemaConsumeSomething {
    public string $key1;
    public string $key2;
    public string $key3;
    public string $key4;
}

return
#[Consumes(APPLICATION_JSON, SchemaConsumeSomething::class)]
fn (#[Body] array $data) => success($data)->as(APPLICATION_JSON);
