<?php

namespace CatPaw\Attributes;

use Attribute;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Traits\CoreAttributeDefinition;

#[Attribute]
class ArrayList implements AttributeInterface {
    use CoreAttributeDefinition;

    public function __construct(public string $className) {
    }
}