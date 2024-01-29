<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;


#[Attribute(flags:Attribute::TARGET_ALL)]
class Summary implements AttributeInterface {
    use CoreAttributeDefinition;

    public function __construct(private string $value) {
    }

    public function getValue():string {
        return $this->value;
    }
}
