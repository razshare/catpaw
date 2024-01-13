<?php

namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\DependenciesOptions;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\Interfaces\OnParameterMount;
use CatPaw\Core\Interfaces\StorageInterface;

use function CatPaw\Core\ok;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Cookie;
use CatPaw\Web\RequestContext;

use CatPaw\Web\SessionOperationsInterface;
use ReflectionParameter;

/**
 * Attach this to a parameter.
 *
 * Catpaw will provide and start (if not already
 * started) the session of the current user.
 *
 * This parameter <b>MUST</b> be of type "array" and it must be a pointer.
 */
#[Attribute]
class Session implements AttributeInterface, StorageInterface, OnParameterMount {
    use CoreAttributeDefinition;

    private static false|SessionOperationsInterface $operations = false;

    public function getStorageInitialValue(): mixed {
        return [];
    }

    public static function setOperations(SessionOperationsInterface $operations):void {
        self::$operations = $operations;
    }

    public static function getOperations():false|SessionOperationsInterface {
        return self::$operations;
    }

    public static function create():Session {
        return new Session();
    }

    private string $id      = '';
    private array  $STORAGE = [];
    private int    $time    = 0;

    public function setId(string $id):void {
        $this->id = $id;
    }

    public function getTime():int {
        return $this->time;
    }

    public function setTime(int $time): void {
        $this->time = $time;
    }

    public function getId():string {
        return $this->id;
    }

    public function &getStorage():array {
        return $this->STORAGE;
    }

    public function setStorage(array &$value):void {
        $this->STORAGE = $value;
    }

    public function &get(string $key):mixed {
        return $this->STORAGE[$key];
    }

    public function set(string $key, mixed $value):void {
        $this->STORAGE[$key] = $value;
    }

    public function unset(string $key):void {
        unset($this->STORAGE[$key]);
    }

    public function has(string $key):bool {
        return isset($this->STORAGE[$key]);
    }


    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe {
        /** @var false|RequestContext $context */
        if (!$context = $options->context) {
            return error("No context found for session.");
        }
        
        $cookies = Cookie::listFromRequest($context->request);

        /** @var Session $session */
        $sessionIdCookie = $cookies["session-id"]  ?? false;
        $sessionId       = $sessionIdCookie->value ?? '';
        $session         = $context->server->sessionOperations->validateSession(id: $sessionId)->try($error);
        if ($error) {
            return error($error);
        }
        
        if (!$session) {
            $session = $context->server->sessionOperations->startSession($sessionId);
        }
        if ($session->getId() !== $sessionId) {
            $sessionIdCookie->addToResponse($context->response);
        }

        $value = $session;

        return ok();
    }
}
