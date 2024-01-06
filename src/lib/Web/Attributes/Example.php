<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Attributes\Entry;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Web\Services\OpenApiService;


#[Attribute]
class Example implements AttributeInterface {
    use CoreAttributeDefinition;
    
    private mixed $example = [];

    public function __construct(
        private array|string|int|float|bool $value,
    ) {
    }

    public function getValue():array {
        return $this->example;
    }

    #[Entry] public function setup(OpenApiService $api):void {
        $this->example = $api->createExample(
            title: $this->value,
            summary: '',
            value: $this->value,
        );
    }
}