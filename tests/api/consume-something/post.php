<?php
use const CatPaw\Web\APPLICATION_JSON;
use CatPaw\Web\Attributes\Consumes;
use CatPaw\Web\Body;
use function CatPaw\Web\success;

class SchemaConsumeSomething {
    public string $key1;
    public string $key2;
    public string $key3;
    public string $key4;
}

return
#[Consumes(APPLICATION_JSON, SchemaConsumeSomething::class)]
fn (Body $body) => success($body->asText())->as(APPLICATION_JSON);
