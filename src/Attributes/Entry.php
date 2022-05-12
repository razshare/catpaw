<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interface\AttributeInterface;
use CatPaw\Attributes\Trait\CoreAttributeDefinition;

#[Attribute]
class Entry implements AttributeInterface{
    use CoreAttributeDefinition;
}