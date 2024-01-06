<?php

namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\DependenciesOptions;
use function CatPaw\error;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Interfaces\OnParameterMount;

use function CatPaw\ok;
use CatPaw\ReflectionTypeManager;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Unsafe;
use CatPaw\Web\RequestContext;
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
#[Attribute]
class Query implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;

    public function __construct(
        private string $name = '',
        private array|string|int|float|bool $example = [],
    ) {
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setExample(array|string|int|float|bool $example): void {
        $this->example = $example;
    }

    public function getExample(): array|string|int|float|bool {
        return $this->example;
    }

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        /** @var RequestContext $context */
        $context = $options->context;
        $type    = ReflectionTypeManager::unwrap($reflection);
        $key     = '' === $this->name?$reflection->getName():$this->name;
        if (!$type) {
            return error("Handler \"$context->key\" must specify at least 1 type for query \"$key\".\n");
        }
        $typeName = $type->getName();

        $result = match ($typeName) {
            "string" => $this->toString($context, $key),
            "int"    => $this->toInteger($context, $key),
            "float"  => $this->toFloat($context, $key),
            "bool"   => $this->toBool($context, $key),
        };

        if ($result->error) {
            return error($result->error);
        }



        if ($result->value) {
            $value = $result->value;
        } elseif (!$reflection->allowsNull()) {
            if (!$reflection->isDefaultValueAvailable()) {
                return error("Handler \"$context->key\" specifies a request query string parameter that is not nullable. Any request query string parameter MUST be nullable or at least provide a default value.\n");
            } else {
                $value = $reflection->getDefaultValue();
            }
        }

        return ok();
    }

    /**
     * @param  RequestContext       $http
     * @return Unsafe<false|string>
     */
    public function toString(RequestContext $http, string $key):Unsafe {
        if (isset($http->requestQueries[$key])) {
            return ok(urldecode($http->requestQueries[$key]));
        }
        return error("Could not convert $key to string.");
    }


    /**
     * @param  RequestContext    $http
     * @return Unsafe<false|int>
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
        return error("Could not convert $key to interger.");
    }


    /**
     * @param  RequestContext $http
     * @return Unsafe<bool>
     */
    private function toBool(RequestContext $http, string $key):Unsafe {
        if (isset($http->requestQueries[$key])) {
            return ok(filter_var(urldecode($http->requestQueries[$key]), FILTER_VALIDATE_BOOLEAN));
        }
        return error("Could not convert $key to bool.");
    }

    /**
     * @param  RequestContext      $http
     * @return Unsafe<false|float>
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
        return error("Could not convert $key to float.");
    }
}
