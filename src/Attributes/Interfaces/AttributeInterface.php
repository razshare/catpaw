<?php

namespace CatPaw\Attributes\Interfaces;

use Amp\Promise;
use CatPaw\Http\HttpContext;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;

interface AttributeInterface {

	public static function findByMethod(ReflectionMethod $reflectionMethod): Promise;

	public static function findByClass(ReflectionClass $reflectionClass): Promise;

	public static function findByProperty(ReflectionProperty $reflectionProperty): Promise;

	/**
	 * Triggers whenever the attribute it assigned to a parameter.
	 * @param ReflectionParameter $parameter the reflection of the parameter.
	 * @param mixed               $value the current value of the parameter.
	 * @param false|HttpContext   $http the HttpContext if available, false otherwise.
	 * @return Promise<void>
	 */
	public function onParameter(ReflectionParameter $parameter, mixed &$value, false|HttpContext $http): Promise;
}