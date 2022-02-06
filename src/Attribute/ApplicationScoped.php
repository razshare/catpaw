<?php
namespace CatPaw\Attribute;

use Attribute;
use CatPaw\Attribute\Interface\AttributeInterface;
use CatPaw\Attribute\Trait\CoreAttributeDefinition;

#[Attribute]
class ApplicationScoped implements AttributeInterface{
    use CoreAttributeDefinition;
}