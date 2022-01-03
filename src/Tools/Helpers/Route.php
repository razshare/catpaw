<?php

namespace CatPaw\Tools\Helpers;

use Amp\LazyPromise;
use Amp\Promise;
use CatPaw\Attributes\Http\PathParam;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Metadata\Meta;
use CatPaw\Http\RouteHandlerContext;
use CatPaw\Tools\Strings;
use Closure;
use Generator;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Razshare\AsciiTable\AsciiTable;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use stdClass;
use function implode;

class Route {

	public static array $routes   = [];
	public static array $patterns = [];

	public static function describe(): string {
		$table = new AsciiTable();
		$table->add("Method", "Path");
		foreach(self::$routes as $method => $paths)
			foreach($paths as $path => $callback)
				if(!str_starts_with($path, '@'))
					$table->add($method, $path);
		return $table->toString().PHP_EOL;
	}

	/**
	 * @param string $path
	 * @param array  $params
	 * @return Generator
	 */
	private static function findPathPatterns(
		string $path,
		array  $params
	): Generator {
		$targets = [
			'pathParams' => [],
			'names'      => [],
			'rawNames'   => [],
		];
		foreach($params as $param) {
			/** @var PathParam $pathParam */
			$pathParam = yield PathParam::findByParameter($param);
			if($pathParam) {
				$optional = $param->isOptional();
				$type = $param->getType()->getName();
				if('' === $pathParam->getRegex())
					switch($type) {
						case 'int':
							$pathParam->setRegex('/^[-+]?[0-9]+$/');
							break;
						case 'float':
							$pathParam->setRegex('/^[-+]?[0-9]+\.[0-9]+$/');
							break;
						case 'string':
							$pathParam->setRegex('/^[^\\/0-9]+$/');
							break;
						case 'bool':
							$pathParam->setRegex('/^(0|1|no?|y(es)?|false|true)$/');
							break;
					}
				$targets['pathParams'][] = $pathParam;
				$targets['names'][] = '\{'.$param->getName().'\}';
				$targets['rawNames'][] = $param->getName();
			}
		}

		if(count($targets['names']) > 0) {
			$localPieces = preg_split('/('.join("|", $targets['names']).')/', $path);
			$pattern = '/(?<={)('.join('|', $targets['rawNames']).')(?=})/';
			$matches = [];
			preg_match_all($pattern, $path, $matches);
			[$names] = $matches;
			$orderedTargets = [
				'pathParams' => [],
				'names'      => [],
				'rawNames'   => [],
			];
			$len = count($targets['rawNames']);
			foreach($names as $name) {
				for($i = 0; $i < $len; $i++) {
					if($targets['rawNames'][$i] === $name) {
						$orderedTargets['pathParams'][] = $targets['pathParams'][$i];
						$orderedTargets['names'][] = $targets['names'][$i];
						$orderedTargets['rawNames'][] = $targets['rawNames'][$i];
					}
				}
			}
			$targets = $orderedTargets;
		} else
			$localPieces = [$path];

		$piecesLen = count($localPieces);

		return function(string $requestedPath) use ($targets, $localPieces, $piecesLen, $path) {
			$variables = [];
			$offset = 0;
			$reconstructed = '';
			$pathParams = $targets['pathParams'];
			for($i = 0; $i < $piecesLen; $i++) {
				$piece = $localPieces[$i];
				$plen = strlen($piece);
				if($piece === ($subrp = substr($requestedPath, $offset, $plen))) {
					$offset += strlen($subrp);
					$reconstructed .= $subrp;
					if(isset($pathParams[$i])) {
						/** @var PathParam $param */
						$param = $pathParams[$i];
						$next = $localPieces[$i + 1]??false;
						if(false !== $next) {
							$end = '' === $next ? strlen($requestedPath) : strpos($requestedPath, $next, $offset);

							if($end === $offset)
								return [false, []];
							$variable = substr($requestedPath, $offset, ($len = $end - $offset));
							if(!preg_match($param->getRegex(), $variable))
								return [false, []];
							$offset += $len;
							$reconstructed .= $variable;
							$variables[$targets['rawNames'][$i]] = urldecode($variable);
						}
					}
				}
			}
			$ok = $reconstructed === $requestedPath;
			return [$ok, $variables];
		};
	}

	/**
	 * @param string        $method
	 * @param string        $path
	 * @param array|Closure $callbacks
	 * @return void
	 */
	private static function initialize(
		string        $method,
		string        $path,
		array|Closure $callbacks,
	): void {
		if(self::$routes[$method][$path]??false) {
			die(Strings::red("Overwriting handler [ $method $path ]\n"));
		}

		if(!is_array($callbacks)) {
			$callbacks = [$callbacks];
		}


		try {
			$len = count($callbacks);
			foreach($callbacks as $i => $callback) {
				$isFilter = $len > 1 && $i < $len - 1;
				$reflection = new ReflectionFunction($callback);
				self::$patterns[$method][$path][] = self::findPathPatterns($path, $reflection->getParameters());
				self::$routes[$method][$path][] = $callback;
				//TODO refactor this attributes section
				$parseAttributes = new LazyPromise(function() use ($method, $path, $isFilter, $reflection, $callback) {
					$context = new class(
						method  : $method,
						path    : $path,
						isFilter: $isFilter,
					) extends RouteHandlerContext {
						public function __construct(
							public string $method,
							public string $path,
							public bool   $isFilter,
						) {
						}
					};

					foreach($reflection->getAttributes() as $attribute) {
						$aname = $attribute->getName();
						/** @var AttributeInterface $ainstance */
						$ainstance = yield $aname::findByFunction($reflection);
						if($ainstance)
							yield $ainstance->onRouteHandler($reflection, $callback, $context);

					}
				});
				$parseAttributes->onResolve(fn() => true);
			}
		} catch(ReflectionException $e) {
			die(Strings::red($e));
		}
	}

	/**
	 * @param ReflectionMethod $reflection_method
	 *
	 * @return array
	 */
	public static function getMappedParameters(ReflectionMethod $reflection_method): array {
		$reflectionParameters = $reflection_method->getParameters();
		$namedAndTypedParams = [];
		$namedParams = [];
		foreach($reflectionParameters as $reflectionParameter) {
			$name = $reflectionParameter->getName();
			$type = $reflectionParameter->getType()->getName();
			$namedAndTypedParams[] = "$type &\$$name";
			$namedParams[] = "\$$name";
		}
		$namedAndTypedParamsString = implode(',', $namedAndTypedParams);
		$namedParamsString = implode(',', $namedParams);
		return [$namedAndTypedParamsString, $namedParamsString];
	}

	/**
	 * Define an alias for an already existing web server path name.
	 *
	 * @param string $method http method of the 2 params
	 * @param string $original path name to capture
	 * @param string $alias alias path name
	 */
	public static function alias(string $method, string $original, string $alias): void {
		if(isset(self::$routes[$method][$original]))
			self::custom($method, $alias, self::$routes[$method][$original]);
		else
			die(Strings::red("Trying to create alias \"$alias\" => \"$original\", but the original route \"$original\" has not beed defined.\n"));
	}

	/**
	 * Define a callback to run when a resource is not found.
	 *
	 * @param array|Closure $callback
	 *
	 * @return void
	 */
	public static function notFound(array|Closure $callback): void {
		static::copy('@404', $callback);
		static::delete('@404', $callback);
		static::get('@404', $callback);
		static::head('@404', $callback);
		static::link('@404', $callback);
		static::lock('@404', $callback);
		static::options('@404', $callback);
		static::patch('@404', $callback);
		static::post('@404', $callback);
		static::propfind('@404', $callback);
		static::purge('@404', $callback);
		static::put('@404', $callback);
		static::unknown('@404', $callback);
		static::unlink('@404', $callback);
		static::unlock('@404', $callback);
		static::view('@404', $callback);
	}


	/**
	 * Define an event callback for a custom http method.
	 *
	 * @param string        $method the name of the http method.
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function custom(string $method, string $path, array|Closure $callback): void {
		static::initialize($method, $path, $callback);
	}

	/**
	 * Define an event callback for the "COPY" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function copy(string $path, array|Closure $callback): void {
		static::initialize('COPY', $path, $callback);

	}

	/**
	 * Define an event callback for the "COPY" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function delete(string $path, array|Closure $callback): void {
		static::initialize('DELETE', $path, $callback);
	}

	/**
	 * Define an event callback for the "COPY" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 * @return void
	 */
	public static function get(string $path, array|Closure $callback): void {
		static::initialize('GET', $path, $callback);
	}

	/**
	 * Define an event callback for the "HEAD" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function head(string $path, array|Closure $callback): void {
		static::initialize('HEAD', $path, $callback);
	}

	/**
	 * Define an event callback for the "LINK" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function link(string $path, array|Closure $callback): void {
		static::initialize('LINK', $path, $callback);
	}

	/**
	 * Define an event callback for the "LOCK" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function lock(string $path, array|Closure $callback): void {
		static::initialize('LOCK', $path, $callback);
	}

	/**
	 * Define an event callback for the "OPTIONS" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function options(string $path, array|Closure $callback): void {
		static::initialize('OPTIONS', $path, $callback);
	}

	/**
	 * Define an event callback for the "PATCH" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function patch(string $path, array|Closure $callback): void {
		static::initialize('PATCH', $path, $callback);
	}

	/**
	 * Define an event callback for the "POST" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function post(string $path, array|Closure $callback): void {
		static::initialize('POST', $path, $callback);
	}

	/**
	 * Define an event callback for the "PROPFIND" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function propfind(string $path, array|Closure $callback): void {
		static::initialize('PROPFIND', $path, $callback);
	}

	/**
	 * Define an event callback for the "PURGE" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function purge(string $path, array|Closure $callback): void {
		static::initialize('PURGE', $path, $callback);
	}

	/**
	 * Define an event callback for the "PUT" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function put(string $path, array|Closure $callback): void {
		static::initialize('PUT', $path, $callback);
	}

	/**
	 * Define an event callback for the "UNKNOWN" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function unknown(string $path, array|Closure $callback): void {
		static::initialize('UNKNOWN', $path, $callback);
	}

	/**
	 * Define an event callback for the "UNLINK" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function unlink(string $path, array|Closure $callback): void {
		static::initialize('UNLINK', $path, $callback);
	}

	/**
	 * Define an event callback for the "UNLOCK" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function unlock(string $path, array|Closure $callback): void {
		static::initialize('UNLOCK', $path, $callback);
	}

	/**
	 * Define an event callback for the "VIEW" http method.
	 *
	 * @param string        $path the path the event should listen to.
	 * @param array|Closure $callback the callback to execute.
	 */
	public static function view(string $path, array|Closure $callback): void {
		static::initialize('VIEW', $path, $callback);
	}
}
