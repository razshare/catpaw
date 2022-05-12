<?php
namespace CatPaw\Attribute;

use Attribute;
use CatPaw\Attribute\Interfaces\AttributeInterface;
use CatPaw\Attribute\Traits\CoreAttributeDefinition;

/**
 * Attach this attribute to a class.
 *
 * Catpaw will treat it as a singleton.
 */
#[Attribute]
class Singleton implements AttributeInterface{
    use CoreAttributeDefinition;
}