<?php
namespace CatPaw\Attribute;

use Attribute;

/**
 * Attach this attribute to a class.
 *
 * Catpaw will treat it as a service
 * (works the same as a Singleton for now).
 */
#[Attribute]
class Service extends Singleton{}