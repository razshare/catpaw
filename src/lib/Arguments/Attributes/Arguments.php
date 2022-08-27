<?php
namespace CatPaw\Arguments\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;

use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use ReflectionParameter;

#[Attribute]
class Arguments implements AttributeInterface {
    use CoreAttributeDefinition;


    public function onParameter(ReflectionParameter $reflection, mixed &$value, mixed $context) {
    }
}