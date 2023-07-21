<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Traits\CoreAttributeDefinition;

#[Attribute]
class Entry implements AttributeInterface {
    use CoreAttributeDefinition;
}