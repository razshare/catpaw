<?php

use const CatPaw\Web\APPLICATION_JSON;
use CatPaw\Web\Attributes\Body;

use CatPaw\Web\Attributes\Consumes;

class SchemaConsumeSomething {
    public string $key1;
    public string $key2;
    public string $key3;
    public string $key4;
}

return
#[Consumes(SchemaConsumeSomething::class, APPLICATION_JSON)]
fn (#[Body] array $data) => $data;