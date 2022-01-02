<?php

namespace CatPaw\Attributes\Http;

use Amp\LazyPromise;
use Amp\Promise;
use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Http\HttpContext;
use ReflectionParameter;

#[Attribute]
class RequestHeader implements AttributeInterface {
	use CoreAttributeDefinition;

	public function __construct(private string $key) { }

	public function onParameter(ReflectionParameter $parameter, mixed &$value, false|HttpContext $http): Promise {
		return new LazyPromise(function() use (
			$parameter,
			&$value,
			$http
		) {
			$value = $http->request->getHeaderArray($this->key);
		});
	}
}