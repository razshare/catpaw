<?php

namespace CatPaw\Http;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\LazyPromise;
use Amp\Promise;
use Amp\Success;
use Amp\Websocket\Client;
use Amp\Websocket\Code;
use Amp\Websocket\Server\ClientHandler;
use Amp\Websocket\Server\Gateway;
use Amp\Websocket\Server\Websocket;
use CatPaw\Attributes\Http\Consumes;
use CatPaw\Attributes\Http\Produces;
use CatPaw\CatPaw;
use CatPaw\Sessions\SessionOperationsInterface;
use CatPaw\Tools\Caster;
use CatPaw\Tools\Helpers\Factory;
use CatPaw\Tools\Helpers\Route;
use CatPaw\Tools\Helpers\WebSocketInterface;
use CatPaw\Tools\XMLSerializer;
use Closure;
use Exception;
use Generator;
use ReflectionException;
use ReflectionFunction;
use SplFixedArray;
use stdClass;
use Throwable;
use function Amp\call;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function json_encode;

class HttpInvoker {

	/**
	 * @param SessionOperationsInterface $sops
	 * @param false|Response             $badRequestNoContentType
	 * @param false|Response             $badRequestCantConsume
	 */
	public function __construct(
		private SessionOperationsInterface $sops,
		private false|Response             $badRequestNoContentType = false,
		private false|Response             $badRequestCantConsume = false,

	) {
		if(!$this->badRequestNoContentType)
			$this->badRequestNoContentType = new Response(Status::BAD_REQUEST, [], '');
		if(!$this->badRequestCantConsume)
			$this->badRequestCantConsume = new Response(Status::BAD_REQUEST, [], '');

	}

	/**
	 * @param Request $httpRequest
	 * @param string  $httpRequestPath
	 * @param string  $httpRequestMethod
	 * @param array   $httpRequestPathParameters
	 * @return Generator
	 * @throws ReflectionException
	 * @throws Throwable
	 */
	public function invoke(
		Request $httpRequest,
		string  $httpRequestMethod,
		string  $httpRequestPath,
		array   $httpRequestPathParameters,
	): Generator {
		$httpRequestContentType = $httpRequest->getHeader("Content-Type")??'*/*';
		$callbacks = Route::$routes[$httpRequestMethod][$httpRequestPath];
		$len = count($callbacks);
		for($i = 0; $i < $len; $i++) {
			/** @var true|Response $response */
			$response = yield from $this->next(
				httpRequest              : $httpRequest,
				httpRequestMethod        : $httpRequestMethod,
				httpRequestPath          : $httpRequestPath,
				httpRequestPathParameters: $httpRequestPathParameters[$i]??[],
				httpRequestContentType   : $httpRequestContentType,
				callback                 : $callbacks[$i],
				isMiddleware             : $i < $len - 1
			);

			if($response !== true)
				return $response;
		}
	}

	private array $cache = [];
	private const REFLECTION = 0;
	private const CONSUMES   = 1;
	private const PRODUCES   = 2;

	/**
	 * @param Request $httpRequest
	 * @param string  $httpRequestMethod
	 * @param string  $httpRequestPath
	 * @param array   $httpRequestPathParameters
	 * @param string  $httpRequestContentType
	 * @param Closure $callback
	 * @param bool    $isMiddleware
	 * @return Generator
	 * @throws ReflectionException
	 * @throws Throwable
	 */
	private function next(
		Request $httpRequest,
		string  $httpRequestMethod,
		string  $httpRequestPath,
		array   $httpRequestPathParameters,
		string  $httpRequestContentType,
		Closure $callback,
		bool    $isMiddleware
	): Generator {
		if(!isset($this->cache[$httpRequestMethod][$httpRequestPath])) {
			$reflection = new ReflectionFunction($callback);
			$consumes = yield Consumes::findByFunction($reflection);
			$produces = yield Produces::findByFunction($reflection);

			$this->cache[$httpRequestMethod][$httpRequestPath] = new SplFixedArray(3);

			$this->cache[$httpRequestMethod][$httpRequestPath][self::REFLECTION] = $reflection;
			$this->cache[$httpRequestMethod][$httpRequestPath][self::CONSUMES] = $consumes;
			$this->cache[$httpRequestMethod][$httpRequestPath][self::PRODUCES] = $produces;
		}

		/** @var ReflectionFunction $reflection */
		$reflection = $this->cache[$httpRequestMethod][$httpRequestPath][self::REFLECTION];
		/** @var Consumes $consumes */
		$consumes = $this->cache[$httpRequestMethod][$httpRequestPath][self::CONSUMES];
		/** @var Produces $produces */
		$produces = $this->cache[$httpRequestMethod][$httpRequestPath][self::PRODUCES];


		/** @var false|Consumes $produces */
		if($consumes) {
			$consumables = $this->filterConsumedContentType($consumes);
			$canConsume = false;
			foreach($consumables as $consumesItem) {
				if($httpRequestContentType === $consumesItem) {
					$canConsume = true;
					break;
				}
			}
			if(!$canConsume) {
				$consumableString = implode(',', $consumables);
				throw new Exception(
					message: "Bad request on '$httpRequestMethod $httpRequestPath', "
							 ."can only consume Content-Type '$consumableString'; provided '$httpRequestContentType'."
				);
			}
		}

		$queryChunks = explode('&', preg_replace('/^\?/', '', $httpRequest->getUri()->getQuery(), 1));
		$query = [];

		foreach($queryChunks as $chunk) {
			$split = explode('=', $chunk);
			$len = count($split);
			if(2 === $len) {
				$query[urldecode($split[0])] = urldecode($split[1]);
			} else if(1 === $len && '' !== $split[0]) {
				$query[urldecode($split[0])] = true;
			}
		}

		$http = new class(
			sessionOperations: $this->sops,
			eventID          : "$httpRequestMethod:$httpRequestPath",
			query            : $query,
			params           : $httpRequestPathParameters,
			request          : $httpRequest,
			response         : new Response(Status::OK, [], ''),
		) extends HttpContext {
			public function __construct(
				public SessionOperationsInterface $sessionOperations,
				public string                     $eventID,
				public array                      $params,
				public array                      $query,
				public Request                    $request,
				public Response                   $response,
			) {
			}
		};

		$parameters = [];
		yield Factory::dependencies($reflection, $parameters, $http);

		$value = $callback(...$parameters);

		if($value instanceof Generator)
			$value = yield from $value;
		else if($value instanceof Promise)
			$value = yield $value;

		if(($sessionIDCookie = $http->response->getCookie("session-id")??false))
			yield $this->sops->persistSession($sessionIDCookie->getValue());

		if($isMiddleware && $value === true)
			return true;

		if($value instanceof WebSocketInterface) {
			return $this->websocket($value)->handleRequest($httpRequest);
		}

		return $this->response(
			http    : $http,
			produces: $produces,
			value   : $value,
		);
	}

	/**
	 * @throws Throwable
	 */
	private function response(
		HttpContext    $http,
		false|Produces $produces,
		mixed          $value,
	): Response {
		if($value instanceof Response) {
			foreach($http->response->getHeaders() as $k => $v)
				if(!$value->hasHeader($k))
					$value->setHeader($k, $v);

			foreach($http->response->getCookies() as $cookie)
				$value->setCookie($cookie);

			$value->setStatus($value->getStatus());

			$http->response = $value;
			$this->adaptResponse($http, $produces, false, true);
		} else {
			$this->adaptResponse($http, $produces, $value);
		}

		return $http->response;
	}

	private function adaptResponse(
		HttpContext    $http,
		false|Produces $produces,
		mixed          $value,
		bool           $ignoreValue = false
	): void {
		$any = false;

		$acceptable = explode(",", $http->request->getHeader("Accept")??"text/plain");
		$producedContentTypes = $produces ? $produces->getContentType() : [$http->response->getHeader("content-type")??"text/plain"];


		foreach($acceptable as $acceptableContentType) {
			if(str_starts_with($acceptableContentType, "*/*")) {
				$any = true;
				continue;
			}
			if(in_array($acceptableContentType, $producedContentTypes)) {
				if($value)
					$http->response->setBody($this->transformResponseBody($acceptableContentType, $value));

				$http->response->setHeader("Content-Type", $acceptableContentType);
				return;
			}
		}


		if($any) {
			if(!$ignoreValue)
				$http->response->setBody($this->transformResponseBody($producedContentTypes[0], $value));
			$http->response->setHeader("Content-Type", $producedContentTypes[0]);
		}
	}

	/**
	 * @param string $contentType
	 * @param mixed  $value
	 * @return string
	 */
	private function transformResponseBody(
		string $contentType,
		mixed  $value,
	): string {
		return match ($contentType) {
			"application/json"            => json_encode($value)??"",
			"application/xml", "text/xml" => is_array($value)
				? XMLSerializer::generateValidXmlFromArray($value)??""
				: (is_object($value)
					? (
						XMLSerializer::generateValidXmlFromObj(
							Caster::cast($value, stdClass::class)
						)??""
					)
					: XMLSerializer::generateValidXmlFromArray([$value])??""),
			default                       => is_array($value) || is_object($value)
				? json_encode($value)??""
				: $value??""
		};
	}

	private function websocket(WebSocketInterface $wsi): Websocket {
		$websocket = new Websocket(new class($wsi) implements ClientHandler {
			public function __construct(private WebSocketInterface $wsi) { }

			public function handleHandshake(Gateway $gateway, Request $request, Response $response): Promise {
				return new LazyPromise(function() use ($gateway, $request, $response) {
					try {
						$r = $this->wsi->onStart($gateway);
						if($r instanceof Generator) yield from $r;

					} catch(Throwable $e) {
						$r = $this->wsi->onError($e);
						if($r instanceof Generator) yield from $r;
					}
					return new Success($response);
				});
			}

			public function handleClient(Gateway $gateway, Client $client, Request $request, Response $response): Promise {
				return call(function() use ($gateway, $client): Generator {
					try {
						while($message = yield $client->receive()) {
							$r = $this->wsi->onMessage($message, $gateway, $client);
							if($r instanceof Generator) yield from $r;
						}
						try {
							$client->onClose(function(...$args) {
								$r = $this->wsi->onClose(...$args);
								if($r instanceof Generator) yield from $r;
							});

						} catch(Throwable $e) {
							$r = $this->wsi->onError($e);
							if($r instanceof Generator) yield from $r;

							$client->close(Code::ABNORMAL_CLOSE);
						}
					} catch(Throwable $e) {
						$r = $this->wsi->onError($e);
						if($r instanceof Generator) yield from $r;
						$client->close(Code::ABNORMAL_CLOSE);
					}
				});
			}
		});

		$websocket->onStart(CatPaw::getHttpServer());
		$websocket->onStop(CatPaw::getHttpServer());

		return $websocket;
	}

	private function filterConsumedContentType(
		Consumes $consumes,
	): array {
		$consumed = $consumes->getContentType();
		return array_filter($consumed, fn($type) => !empty($type));
	}
}