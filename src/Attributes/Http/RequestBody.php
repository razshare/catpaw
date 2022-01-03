<?php

namespace CatPaw\Attributes\Http;

use Amp\ByteStream\IteratorStream;
use Amp\Http\Server\Request;
use Amp\LazyPromise;
use Amp\Producer;
use Amp\Promise;
use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Http\HttpContext;
use CatPaw\Sessions\SessionOperationsInterface;
use CatPaw\Tools\Helpers\Parsing\BodyParser;
use Exception;
use ReflectionParameter;

/**
 * Attach this to a parameter.
 *
 * Catpaw will provide the body of the request
 * (if available).
 *
 * <hr/>
 *
 * The server will attempt to cast the request body
 * as the type of this parameter.
 *
 * <hr/>
 *
 * If this parameter is of type "string", the server
 * will simply pass in the request body as is.
 */
#[Attribute]
class RequestBody implements AttributeInterface {
	use CoreAttributeDefinition;

	public function onParameter(ReflectionParameter $reflection, mixed &$value, false|HttpContext $http): Promise {
		return new LazyPromise(function() use (
			$reflection,
			&$value,
			$http
		) {
			$className = $reflection->getType()->getName()??'';
			$value = match ($className) {
				"array"                             => $this->toArray(
					body       : yield $http->request->getBody()->buffer(),
					contentType: $http->request->getHeader("Content-Type"),
				),

				"string"                            => yield $http->request->getBody()->buffer(),

				"int"                               => $this->toInteger(
					body: yield $http->request->getBody()->buffer(),
				),

				"float"                             => $this->toFloat(
					body: yield $http->request->getBody()->buffer(),
				),

				\Amp\Http\Server\RequestBody::class => yield $http->request->getBody(),

				IteratorStream::class               => $this->toIteratorStream(
					body: yield $http->request->getBody(),
				),

				default                             => $this->toObject(
					body       : yield $http->request->getBody()->buffer(),
					contentType: $http->request->getHeader("Content-Type"),
					className  : $className
				),
			};
		});
	}

	/**
	 * @throws Exception
	 */
	private function toArray(string $body, string $contentType): array {
		return BodyParser::parse(
			$body,
			$contentType,
			false,
			true
		);
	}

	/**
	 * @throws Exception
	 */
	private function toInteger(string $body): int {
		if(is_numeric($body)) {
			return (int)$body;
		} else {
			throw new Exception('Body was expected to be numeric, but non numeric value has been provided instead:'.$body);
		}
	}

	/**
	 * @throws Exception
	 */
	private function toFloat(string $body): int {
		if(is_numeric($body)) {
			return (float)$body;
		} else {
			throw new Exception('Body was expected to be numeric, but non numeric value has been provided instead:'.$body);
		}
	}


	private function toIteratorStream(\Amp\Http\Server\RequestBody $body): IteratorStream {
		return new IteratorStream(new Producer(function($emit) use ($body) {
			while(($chunk = yield $body->read()) !== null)
				yield $emit($chunk);
		}));
	}

	/**
	 * @throws Exception
	 */
	private function toObject(string $body, string $contentType, string $className): object {
		return BodyParser::parse($body, $contentType, $className);
	}
}