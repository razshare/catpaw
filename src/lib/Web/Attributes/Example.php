<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Web\Services\OpenApiService;


#[Attribute]
class Example implements AttributeInterface {
    use CoreAttributeDefinition;
    
    private mixed $example = [];

    public function __construct(
        private readonly array|string|int|float|bool $value,
    ) {
    }

    public function getValue():array {
        return $this->example;
    }

    #[Entry] public function setup(OpenApiService $api):void {
        $this->example = $api->createExample(
            title  : $this->value,
            value  : $this->value,
        );
    }
}