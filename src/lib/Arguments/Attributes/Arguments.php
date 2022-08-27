<?php
namespace CatPaw\Arguments\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;

use CatPaw\Attributes\Traits\CoreAttributeDefinition;

#[Attribute]
class Arguments implements AttributeInterface {
    use CoreAttributeDefinition;

    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context) {
        return;
    }
}