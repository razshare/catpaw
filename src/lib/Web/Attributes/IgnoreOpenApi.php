<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;

#[Attribute]
class IgnoreOpenApi implements AttributeInterface {
    use CoreAttributeDefinition;
}