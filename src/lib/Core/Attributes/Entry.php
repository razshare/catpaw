<?php
namespace CatPaw\Core\Attributes;

use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;



#[Attribute(flags: Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Entry implements AttributeInterface {
    use CoreAttributeDefinition;
}
