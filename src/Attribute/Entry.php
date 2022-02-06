<?php
namespace CatPaw\Attribute;

use Attribute;
use CatPaw\Attribute\Interface\AttributeInterface;
use CatPaw\Attribute\Trait\CoreAttributeDefinition;

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