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
 * Get a request header field.
 * 
 * Some examples:
 * 
 * - `#[Header('user-agent')] $ua`
 * - `#[Header] $authorization` (this is equivalent to `#[Header('authorization')] $authorization`)
 * 
 * 
 * @package CatPaw\Web\Attributes
 */
#[Attribute]
class Header implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;

    public function __construct(
        private string $key = '',
        private array|string|int|float|bool $example = [],
    ) {
    }

    public function getKey():string {
        return $this->key;
    }


    public function setExample(array|string|int|float|bool $example): void {
        $this->example = $example;
    }

    public function getExample(): array|string|int|float|bool {
        return $this->example;
    }
    
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        /** @var false|RequestContext $context */
        $context = $options->context;

        if (!$context) {
            return error("No context found for header $this->key.");
        }

        if (!$this->key) {
            $this->key = $reflection->getName();
        }

        $className = ReflectionTypeManager::unwrap($reflection)?->getName() ?? '';

        $value = match ($className) {
            'string' => $context->request->getHeader($this->key)         ?? '',
            'bool'   => (bool)$context->request->getHeader($this->key)   ?? '',
            'int'    => (int)$context->request->getHeader($this->key)    ?? '',
            'double' => (double)$context->request->getHeader($this->key) ?? '',
            'float'  => (float)$context->request->getHeader($this->key)  ?? '',
            'array'  => $context->request->getHeaderPairs($this->key),
            default  => $context->request->getHeader($this->key) ?? '',
        } ?? $value;

        return ok();
    }
}
