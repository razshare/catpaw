<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\DependenciesOptions;
use function CatPaw\error;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Interfaces\OnParameterMount;

use function CatPaw\ok;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Unsafe;
use CatPaw\Web\Cookie;

use CatPaw\Web\RequestContext;
use ReflectionParameter;

#[Attribute]
class SessionId implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;
    
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        $value = '';

        /** @var RequestContext $context */
        if (!$context = $options->context) {
            return error("No context found for session id.");
        }

        if ($cookie = Cookie::findFromRequestContextByName($context, 'session-id')) {
            $value = $cookie->value;
        }

        return ok();
    }
}