<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Traits\CoreAttributeDefinition;

/**
 * Attach this attribute to a class.
 *
 * Catpaw will treat it as a singleton.
 */
#[Attribute]
class Singleton implements AttributeInterface {
    use CoreAttributeDefinition;
}