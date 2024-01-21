<?php

namespace CatPaw\Core\Attributes;

use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Traits\CoreAttributeDefinition;

/**
 * This attribute exists for the sole purpose of describing arrays to the open api service.\
 * \
 * Attach this attribute to a class property as `HashMap<T>`.\
 * The open api service will interpret it as `array<string,T>`.
 */
#[Attribute]
class HashMap implements AttributeInterface {
    use CoreAttributeDefinition;

    public function __construct(public string $className) {
    }
}
