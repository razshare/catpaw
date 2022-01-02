<?php

namespace CatPaw;

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\LazyPromise;
use Amp\Loop;
use Amp\Promise;
use Amp\Socket\BindContext;
use Amp\Socket\Server;
use Amp\Socket\ServerTlsContext;
use CatPaw\Attributes\Sessions\Session;
use CatPaw\Configs\MainConfiguration;
use CatPaw\Http\HttpInvoker;
use CatPaw\Sessions\SessionManager;
use CatPaw\Tools\Helpers\Route;
use CatPaw\Tools\Strings;
use Exception;
use Generator;
use Throwable;
use function count;
use const SIGINT;

class CatPaw {

	private static false|HttpServer $httpServer = false;

	private function __construct() { }

	public static function getHttpServer(): false|HttpServer {
		return self::$httpServer;
	}

	public static function startWebServer(
		MainConfiguration $config
	): Promise {
		return new LazyPromise(function() use ($config) {
			if(!$config->logger)
				die(Strings::red("Please specify a logger instance.\n"));

			$invoker = new HttpInvoker(Session::getOperations());

			$sockets = [];

			if(!is_iterable($config->httpInterfaces))
				$interfaces = [$config->httpInterfaces];
			else
				$interfaces = $config->httpInterfaces;


			foreach($interfaces as $interface)
				$sockets[] = Server::listen($interface);

			if($config->pemCertificate) {
				$context = (new BindContext)
					->withTlsContext((new ServerTlsContext)
										 ->withDefaultCertificate($config->pemCertificate));

				if(!is_iterable($config->httpSecureInterfaces))
					$secureInterfaces = [$config->httpSecureInterfaces??[]];
				else
					$secureInterfaces = $config->httpSecureInterfaces;

				foreach($secureInterfaces as $interface)
					if($interface)
						$sockets[] = Server::listen($interface, $context);

			} else if($config->httpSecureInterfaces && count($config->httpSecureInterfaces) > 0)
				$config->logger->critical("Server could not bind to the secure network interfaces because no pem certificate has been provided.");

			if(0 >= count($sockets)) {
				$config->logger->error("At least one network interface must be provided in order to start the server.");
				die();
			}

			$server = self::$httpServer = new HttpServer(
				$sockets,
				new CallableRequestHandler(
					static fn(Request $request) => static::serve($config, $request, $invoker)
				),
				$config->logger
			);

			if($config->beforeStart) {
				$result = ($config->beforeStart)();
				if($result instanceof Generator)
					yield from $result;
			}

			$server->setErrorHandler(new class implements \Amp\Http\Server\ErrorHandler {
				public function handleError(int $statusCode, string $reason = null, Request $request = null): Promise {
					return new LazyPromise(function() use ($statusCode, $reason, $request) {

					});
				}
			});


			yield $server->start();

			Loop::onSignal(SIGINT, static function(string $watcherId) use ($server) {
				Loop::cancel($watcherId);
				yield $server->stop();
				Loop::stop();
				die(0);
			});
		});
	}


	/**
	 * @throws Exception
	 * @throws Throwable
	 */
	private static function serve(MainConfiguration $config, Request $httpRequest, HttpInvoker $invoker): Generator {
		$httpRequestMethod = $httpRequest->getMethod();
		$httpRequestUri = $httpRequest->getUri();
		$httpRequestPath = $httpRequestUri->getPath();

		//check if request matches any exposed endpoint and extract parameters
		[$httpRequestPath, $httpRequestPathParameters] = yield from static::usingPath($httpRequestMethod, $httpRequestPath, Route::$routes);

		if(!$httpRequestPath) {
			$response = yield from $invoker->invoke(
				httpRequest              : $httpRequest,
				httpRequestMethod        : $httpRequestMethod,
				httpRequestPath          : '@404',
				httpRequestPathParameters: $httpRequestPathParameters,
			);

			if(!$response) {
				$config->logger->error("There is no event listener or controller that manages \"404 Not Found\" requests, serving an empty \"500 Internal Server Error\" response instead.");
				$response = new Response(Status::INTERNAL_SERVER_ERROR);
			}
			return $response;
		}

		try {
			$response = yield from $invoker->invoke(
				httpRequest              : $httpRequest,
				httpRequestMethod        : $httpRequestMethod,
				httpRequestPath          : $httpRequestPath,
				httpRequestPathParameters: $httpRequestPathParameters,
			);

			if(!$response) {
				$config->logger->critical("The path matcher returned a match for \"$httpRequestMethod\" but the invoker couldn't find the function/method to invoke, serving an empty \"500 Internal Server Error\" response instead.");
				$response = new Response(Status::INTERNAL_SERVER_ERROR);
			}
			return $response;
		} catch(Throwable $e) {
			$message = $config->httpShowException ? $e->getMessage() : '';
			$trace = $config->httpShowException && $config->httpShowStackTrace ? "\n".$e->getTraceAsString() : '';
			$config->logger->error($e->getMessage());
			$config->logger->error($e->getTraceAsString());
			return new Response(500, [], $message.$trace);
		}
	}

	private static array $cache = [];

	private static function usingPath(string $httpRequestMethod, string $httpRequestPath, array $callbacks): Generator {
		if(!isset($callbacks[$httpRequestMethod]))
			return [false, []];
		foreach($callbacks[$httpRequestMethod] as $localPath => $callback) {
			if(!isset(self::$cache[$httpRequestMethod])) {
				self::$cache[$httpRequestMethod] = [];
			}
			if(isset(self::$cache[$httpRequestMethod][$localPath])) {
				$patterns = self::$cache[$httpRequestMethod][$localPath];
			} else {
				$patterns = [];
				foreach(Route::$patterns[$httpRequestMethod][$localPath] as $g) {
					$patterns[] = yield from $g;
				}
				self::$cache[$httpRequestMethod][$localPath] = $patterns;
			}
			$ok = false;
			$allParams = [];
			/** @var callable $pattern */
			foreach($patterns as $pattern) {
				[$k, $params] = $pattern($httpRequestPath);
				if($k) {
					$ok = true;
					$allParams[] = $params;
				}
			}
			if($ok) {
				return [$localPath, $allParams];
			}
		}
		return [false, []];
	}
}