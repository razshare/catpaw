<?php
namespace CatPaw\Core\Attributes;

use Attribute;

/**
 * Attach this attribute to a class and catpaw will treat it as a service.
 */
#[Attribute(flags: Attribute::TARGET_CLASS)]
class Service extends Singleton {
}
