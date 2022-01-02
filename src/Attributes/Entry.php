<?php
namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;

/**
 * This attribute has different meanings within
 * different contexts.
 *
 * Check the "@see" section to see where this
 * attribute can be used.
 *
 * @see FilterItem
 * @see ApplicationScoped
 */
#[Attribute]
class Entry implements AttributeInterface{
    use CoreAttributeDefinition;
}