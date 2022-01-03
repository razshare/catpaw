<?php

namespace CatPaw\Attributes\Sessions;

use Amp\Http\Cookie\ResponseCookie;
use Amp\LazyPromise;
use Amp\Promise;
use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Http\HttpContext;
use CatPaw\Sessions\SessionOperationsInterface;
use JetBrains\PhpStorm\Pure;
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
class Session implements AttributeInterface {
	use CoreAttributeDefinition;

	private string $id;
	private array  $STORAGE = [];
	private int    $time;

	private static false|SessionOperationsInterface $operations = false;

	public static function setOperations(SessionOperationsInterface $operations): void {
		self::$operations = $operations;
	}

	public static function getOperations():false|SessionOperationsInterface {
		return self::$operations;
	}

	#[Pure] public static function create(): Session {
		return new Session();
	}

	public function setId(string $id): void {
		$this->id = $id;
	}

	public function getTime(): int {
		return $this->time;
	}

	public function setTime(int $time): void {
		$this->time = $time;
	}

	public function getId(): string {
		return $this->id;
	}

	public function &storage(): array {
		return $this->STORAGE;
	}

	public function setStorage(array &$storage): void {
		$this->STORAGE = $storage;
	}

	public function &get(string $key) {
		return $this->STORAGE[$key];
	}

	public function set(string $key, $object): void {
		$this->STORAGE[$key] = $object;
	}

	public function remove(string $key): void {
		unset($this->STORAGE[$key]);
	}

	public function has(string $key): bool {
		return isset($this->STORAGE[$key]);
	}


	public function onParameter(ReflectionParameter $reflection, mixed &$value, false|HttpContext $http): Promise {
		return new LazyPromise(function() use (
			$reflection,
			&$value,
			$http
		) {
			if(!$http) return;
			/** @var Session $session */
			$sessionIDCookie = $http->request->getCookie("session-id")??false;
			$sessionID = $sessionIDCookie ? $sessionIDCookie->getValue() : '';
			$session = yield $http->sessionOperations->validateSession(id: $sessionID);
			if($sessionID !== $session->getId())
				$http->response->setCookie(new ResponseCookie("session-id", $session->getId()));

			$value = $session;
		});
	}
}