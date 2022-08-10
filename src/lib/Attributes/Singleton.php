<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;

/**
 * Attach this attribute to a class.
 *
 * Catpaw will treat it as a singleton.
 */
#[Attribute]
class Singleton implements AttributeInterface {
    use CoreAttributeDefinition;
}