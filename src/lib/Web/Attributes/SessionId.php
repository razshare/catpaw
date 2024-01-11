<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;

use function CatPaw\Core\ok;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;
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