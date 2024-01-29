<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;

#[Attribute(flags:Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class OperationId implements AttributeInterface {
    use CoreAttributeDefinition;

    public function __construct(private string $value) {
    }

    public function getValue():string {
        return $this->value;
    }
}
