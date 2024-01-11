<?php
namespace CatPaw\Core\Attributes;

use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;


#[Attribute]
class Entry implements AttributeInterface {
    use CoreAttributeDefinition;
}