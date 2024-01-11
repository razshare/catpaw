<?php

namespace CatPaw\Core\Attributes;

use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;

/**
 * This attribute exists for the sole purpose of describing arrays to the open api service.<br/>
 * <br/>
 * Attach this attribute to a class property as <b>ArrayList&lt;T&gt;</b>.<br/>
 * The open api service will interpret it as <b>array&lt;T&gt;</b>. 
 */
#[Attribute]
class ArrayList implements AttributeInterface {
    use CoreAttributeDefinition;

    public function __construct(public string $className) {
    }
}