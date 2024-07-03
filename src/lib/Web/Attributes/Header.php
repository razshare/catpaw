<?php

namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;

use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;

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
#[Attribute(flags:Attribute::TARGET_PARAMETER)]
class Header implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;

    public function __construct(
        private string $key = '',
    ) {
    }

    public function key():string {
        return $this->key;
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

        $className = ReflectionTypeManager::unwrap($reflection)->getName();

        $value = match ($className) {
            'bool'   => (bool)($context->request->getHeader($this->key) ?? ''),
            'int'    => (int)($context->request->getHeader($this->key) ?? ''),
            'double' => (double)($context->request->getHeader($this->key) ?? ''),
            'float'  => (float)($context->request->getHeader($this->key) ?? ''),
            'array'  => explode(',', $context->request->getHeader($this->key) ?? ''),
            default  => $context->request->getHeader($this->key) ?? '',
        };

        return ok();
    }
}
