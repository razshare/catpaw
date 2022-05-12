<?php
namespace CatPaw\Attribute;

use Attribute;
use CatPaw\Attribute\Interface\AttributeInterface;
use CatPaw\Attribute\Trait\CoreAttributeDefinition;

#[Attribute]
class Entry implements AttributeInterface{
    use CoreAttributeDefinition;
}