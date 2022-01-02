<?php

namespace CatPaw\Attributes\Http;

use Amp\LazyPromise;
use Amp\Promise;
use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Http\HttpContext;
use Exception;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use ReflectionParameter;

#[Attribute]
class RequestQuery implements AttributeInterface {
	use CoreAttributeDefinition;

	public function __construct(
		private string $name = ''
	) {
	}

	public function setName(string $name): void {
		$this->name = $name;
	}

	public function getName(): string {
		return $this->name;
	}

	public function onParameter(ReflectionParameter $parameter, mixed &$value, false|HttpContext $http): Promise {
		return new LazyPromise(function() use (
			$parameter,
			&$value,
			$http,
		) {
			$classname = $parameter->getType()->getName()??'';
			$result = match ($classname) {
				"string" => $this->toString($http),
				"int"    => $this->toInteger($http),
				"float"  => $this->toFloat($http),
				"bool"   => $this->toBool($http),
			};
			if($result)
				$value = $result;
		});
	}

	/**
	 * @param HttpContext $http
	 * @return false|string
	 */
	#[Pure] public function toString(HttpContext $http): false|string {
		if(isset($http->query[$this->name]))
			return urldecode($http->query[$this->name]);
		return false;
	}


	/**
	 * @param HttpContext $http
	 * @return false|int
	 * @throws Exception
	 */
	public function toInteger(HttpContext $http): false|int {
		if(isset($http->query[$this->name])) {
			$value = urldecode($http->query[$this->name]);
			if(is_numeric($value))
				return (int)$value;
			else
				throw new Exception("RequestQuery $this->name was expected to be numeric, but non numeric value has been provided instead:$value");
		}
		return false;
	}


	/**
	 * @param HttpContext $http
	 * @return bool
	 */
	#[Pure] public function toBool(HttpContext $http): bool {
		if(isset($http->query[$this->name]))
			return filter_var(urldecode($http->query[$this->name]), FILTER_VALIDATE_BOOLEAN);
		return false;
	}

	/**
	 * @param HttpContext $http
	 * @return false|float
	 * @throws Exception
	 */
	public function toFloat(HttpContext $http): false|float {
		if(isset($http->query[$this->name])) {
			$value = urldecode($http->query[$this->name]);
			if(is_numeric($value))
				return (float)$value;
			else
				throw new Exception("RequestQuery $this->name was expected to be numeric, but non numeric value has been provided instead:$value");
		}
		return false;
	}
}