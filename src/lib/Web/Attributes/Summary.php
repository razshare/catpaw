<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Traits\CoreAttributeDefinition;


#[Attribute]
class Summary implements AttributeInterface {
    use CoreAttributeDefinition;
    
    public function __construct(private string $value) {
    }

    public function getValue():string {
        return $this->value;
    }
}