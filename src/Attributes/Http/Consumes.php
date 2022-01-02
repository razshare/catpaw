<?php

namespace CatPaw\Attributes\Http;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;

/**
 * Attach to a function or method.
 *
 * Catpaw will recognize that this function or method
 * <b>MUST</b> consume only the specified
 * <b>Content-Type</b>s.
 *
 * <hr/>
 *
 * Each content type should be separated by commas
 * as described in the http standard.
 *
 * Example of possible values:
 * - \*<span>/</span>\*
 * - text/plain, application/json
 * - image/png, image/jpeg
 */
#[Attribute]
class Consumes implements AttributeInterface {
	use CoreAttributeDefinition;

	private array $contentType;

	public function __construct(string ...$contentType) {
		$this->contentType = $contentType;
	}

	public function getContentType(): array {
		return $this->contentType;
	}
}