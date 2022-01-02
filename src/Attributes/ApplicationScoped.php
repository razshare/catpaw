<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;

#[Attribute]
class ApplicationScoped implements AttributeInterface{
    use CoreAttributeDefinition;
}