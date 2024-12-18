<?php

namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMountInterface;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Web\RequestContext;
use ReflectionException;
use ReflectionParameter;

/**
 * Get a query string field.
 *
 * Some examples:
 *
 * - `#[Query('my-key')] $ua` will retrieve the `"my-key"` query string
 * - `#[Query] $authorization` will retrieve the `"authorization"` query string (it's equivalent to `#[Query("authorization")] $authorization` )
 *
 *
 * @package CatPaw\Web\Attributes
 */
#[Attribute(flags:Attribute::TARGET_PARAMETER)]
class Query implements AttributeInterface, OnParameterMountInterface {
    use CoreAttributeDefinition;

    /**
     * @param  ReflectionParameter $reflectionParameter
     * @param  mixed               $value
     * @param  RequestContext      $context
     * @throws ReflectionException
     * @return Result<None>
     */
    public function resolve(ReflectionParameter $reflectionParameter, mixed &$value, RequestContext $context):Result {
        $type = ReflectionTypeManager::unwrap($reflectionParameter);
        $key  = $reflectionParameter->getName();
        if (!$type) {
            return error("Handler \"$context->key\" must specify at least 1 type for query \"$key\".");
        }
        $typeName = $type->getName();

        $result = match ($typeName) {
            "int"   => $this->toInteger($context, $key),
            "float" => $this->toFloat($context, $key),
            "bool"  => $this->toBool($context, $key),
            default => $this->toString($context, $key),
        };

        $value = $result->unwrap($error);
        if ($error) {
            return error($error);
        }

        if (null === $value) {
            if (!$reflectionParameter->allowsNull()) {
                if (!$reflectionParameter->isDefaultValueAvailable()) {
                    return error(
                        <<<TEXT
                            Handler \"$context->key\" specifies a request query string parameter that is neither nullable nor has a default value.
                            Any request query string parameter MUST be nullable or at least provide a default value.
                            TEXT
                    );
                } else {
                    $value = $reflectionParameter->getDefaultValue();
                }
            }
        }

        return ok();
    }

    /**
     *
     * @param  ReflectionParameter $reflectionParameter
     * @param  mixed               $value
     * @param  DependenciesOptions $options
     * @throws ReflectionException
     * @return Result<None>
     */
    public function onParameterMount(ReflectionParameter $reflectionParameter, mixed &$value, DependenciesOptions $options):Result {
        return $this->resolve($reflectionParameter, $value, $options->context);
    }

    /**
     * @param  RequestContext      $http
     * @param  string              $key
     * @return Result<null|string>
     */
    public function toString(RequestContext $http, string $key):Result {
        if (isset($http->requestQueries[$key])) {
            // @phpstan-ignore return.type
            return ok(urldecode($http->requestQueries[$key]));
        }
        // @phpstan-ignore return.type
        return ok(null);
    }


    /**
     * @param  RequestContext   $http
     * @param  string           $key
     * @return Result<null|int>
     */
    private function toInteger(RequestContext $http, string $key):Result {
        if (isset($http->requestQueries[$key])) {
            $value = urldecode($http->requestQueries[$key]);
            if (is_numeric($value)) {
                // @phpstan-ignore return.type
                return ok((int)$value);
            } else {
                return error("Query $key was expected to be numeric, but non numeric value has been provided instead:$value.");
            }
        }
        // @phpstan-ignore return.type
        return ok(null);
    }


    /**
     * @param  RequestContext    $http
     * @param  string            $key
     * @return Result<null|bool>
     */
    private function toBool(RequestContext $http, string $key):Result {
        if (isset($http->requestQueries[$key])) {
            // @phpstan-ignore return.type
            return ok(filter_var(urldecode($http->requestQueries[$key]), FILTER_VALIDATE_BOOLEAN));
        }
        // @phpstan-ignore return.type
        return ok(null);
    }

    /**
     * @param  RequestContext     $http
     * @param  string             $key
     * @return Result<null|float>
     */
    private function toFloat(RequestContext $http, string $key):Result {
        if (isset($http->requestQueries[$key])) {
            $value = urldecode($http->requestQueries[$key]);
            if (is_numeric($value)) {
                // @phpstan-ignore return.type
                return ok((float)$value);
            } else {
                return error("Query $key was expected to be numeric, but non numeric value has been provided instead:$value.");
            }
        }
        // @phpstan-ignore return.type
        return ok(null);
    }
}