<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;


#[Attribute(flags:Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Tag implements AttributeInterface {
    use CoreAttributeDefinition;

    public function __construct(private string $value) {
    }

    public function value():string {
        return $this->value;
    }
}
