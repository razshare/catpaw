<?php
use const CatPaw\Web\APPLICATION_JSON;
use CatPaw\Web\Attributes\Consumes;
use CatPaw\Web\Body;
use function CatPaw\Web\success;
use Tests\Types\SchemaConsumeSomething;



return
#[Consumes(APPLICATION_JSON, SchemaConsumeSomething::class)]
fn (Body $body) => success($body->text())->as(APPLICATION_JSON);
