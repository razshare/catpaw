<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Web\Interfaces\OpenApiStateInterface;

#[Attribute(flags:Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
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

    #[Entry] public function setup(OpenApiStateInterface $openAPiState):void {
        $this->example = $openAPiState->createExample(
            title  : $this->value,
            value  : $this->value,
        );
    }
}
