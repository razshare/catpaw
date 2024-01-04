<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Traits\CoreAttributeDefinition;

#[Attribute]
class IgnoreOpenApi implements AttributeInterface {
    use CoreAttributeDefinition;
}