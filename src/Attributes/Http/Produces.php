<?php

namespace CatPaw\Attributes\Http;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;

/**
 * Attatch to a function or method.
 *
 * This tell catpaw what type of content it produces.<br/>
 * Example of possible values:
 * - text/plain
 * - application/json
 */
#[Attribute]
class Produces implements AttributeInterface {
	use CoreAttributeDefinition;

	private array $contentType;

	public function __construct(
		string ...$contentType
	) {
		$this->contentType = $contentType;
	}

	public function getContentType(): array {
		return $this->contentType;
	}
}