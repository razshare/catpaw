<?php
namespace CatPaw\Web\Attributes;

use Amp\Http\Cookie\ResponseCookie;
use Attribute;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;
use function CatPaw\Core\ok;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;
use CatPaw\Web\RequestContext;
use ReflectionParameter;

/**
 * Get the current session-id cookie of the client.
 * @package CatPaw\Web\Attributes
 */
#[Attribute(flags:Attribute::TARGET_PARAMETER)]
class SessionId implements AttributeInterface, OnParameterMount {
    use CoreAttributeDefinition;

    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        $value = '';

        /** @var RequestContext $context */
        if (!$context = $options->context) {
            return error("No context found for session id.");
        }

        $cookies = $context->request->getCookies();

        $sessionIdCookie = $cookies['session-id'] ?? false;
        $sessionId       = $sessionIdCookie?$sessionIdCookie->getValue():'';
        $session         = $context->server->sessionOperations->validateSession(id: $sessionId)->try($error);
        if ($error) {
            return error($error);
        }

        if (!$session) {
            $session = $context->server->sessionOperations->startSession($sessionId);
        }

        if ($session->getId() !== $sessionId) {
            $sessionIdCookie    = new ResponseCookie('session-id', $session->getId());
            $context->cookies[] = $sessionIdCookie;
        }

        $value = $session->getId();

        return ok();
    }
}
