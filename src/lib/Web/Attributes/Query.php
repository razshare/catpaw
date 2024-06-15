<?php

namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;
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
class Query implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;

    public function __construct(
        private string $name = '',
    ) {
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     *
     * @param  ReflectionParameter $reflection
     * @param  mixed               $value
     * @param  DependenciesOptions $options
     * @throws ReflectionException
     * @return Unsafe<None>
     */
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        /** @var RequestContext $context */
        $context = $options->context;
        $type    = ReflectionTypeManager::unwrap($reflection);
        $key     = '' === $this->name?$reflection->getName():$this->name;
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

        if ($result->error) {
            return error($result->error);
        }

        if ($result->value) {
            $value = $result->value;
        } elseif (!$reflection->allowsNull()) {
            if (!$reflection->isDefaultValueAvailable()) {
                return error("Handler \"$context->key\" specifies a request query string parameter that is not nullable. Any request query string parameter MUST be nullable or at least provide a default value.");
            } else {
                $value = $reflection->getDefaultValue();
            }
        }

        return ok();
    }

    /**
     * @param  RequestContext $http
     * @param  string         $key
     * @return Unsafe<string>
     */
    public function toString(RequestContext $http, string $key):Unsafe {
        if (isset($http->requestQueries[$key])) {
            return ok(urldecode($http->requestQueries[$key]));
        }
        return ok('');
    }


    /**
     * @param  RequestContext $http
     * @param  string         $key
     * @return Unsafe<int>
     */
    private function toInteger(RequestContext $http, string $key):Unsafe {
        if (isset($http->requestQueries[$key])) {
            $value = urldecode($http->requestQueries[$key]);
            if (is_numeric($value)) {
                return ok((int)$value);
            } else {
                return error("Query $key was expected to be numeric, but non numeric value has been provided instead:$value.");
            }
        }
        return ok(0);
    }


    /**
     * @param  RequestContext $http
     * @param  string         $key
     * @return Unsafe<bool>
     */
    private function toBool(RequestContext $http, string $key):Unsafe {
        if (isset($http->requestQueries[$key])) {
            return ok(filter_var(urldecode($http->requestQueries[$key]), FILTER_VALIDATE_BOOLEAN));
        }
        return ok(false);
    }

    /**
     * @param  RequestContext $http
     * @param  string         $key
     * @return Unsafe<float>
     */
    private function toFloat(RequestContext $http, string $key):Unsafe {
        if (isset($http->requestQueries[$key])) {
            $value = urldecode($http->requestQueries[$key]);
            if (is_numeric($value)) {
                return ok((float)$value);
            } else {
                return error("Query $key was expected to be numeric, but non numeric value has been provided instead:$value.");
            }
        }
        return ok(0.0);
    }
}
