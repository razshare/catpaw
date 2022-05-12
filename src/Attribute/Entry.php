<?php
namespace CatPaw\Attribute;

use Attribute;
use CatPaw\Attribute\Interfaces\AttributeInterface;
use CatPaw\Attribute\Traits\CoreAttributeDefinition;

#[Attribute]
class Entry implements AttributeInterface{
    use CoreAttributeDefinition;
}