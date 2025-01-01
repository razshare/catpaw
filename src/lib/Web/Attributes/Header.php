<?php

namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\ContainerContext;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMountInterface;

use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Result;
use CatPaw\Core\Traits\CoreAttributeDefinition;

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
class Header implements AttributeInterface, OnParameterMountInterface {
    use CoreAttributeDefinition;

    public function __construct(
        private string $key = '',
    ) {
    }

    public function key():string {
        return $this->key;
    }

    public function onParameterMount(ReflectionParameter $reflectionParameter, mixed &$value, ContainerContext $options):Result {
        /** @var false|RequestContext $context */
        $context = $options->data;

        if (!$context) {
            return error("No context found for header $this->key.");
        }

        if (!$this->key) {
            $this->key = $reflectionParameter->getName();
        }

        $className = ReflectionTypeManager::unwrap($reflectionParameter)->getName();

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
