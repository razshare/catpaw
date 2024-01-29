<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Web\Services\OpenApiService;


#[Attribute(flags:Attribute::TARGET_PARAMETER)]
class Example implements AttributeInterface {
    use CoreAttributeDefinition;

    private mixed $example = '';

    public function __construct(
        private readonly mixed $value,
    ) {
    }

    public function getValue():mixed {
        return $this->example;
    }

    #[Entry] public function setup(OpenApiService $api):void {
        $this->example = $api->createExample(
            title  : $this->value,
            value  : $this->value,
        );
    }
}
