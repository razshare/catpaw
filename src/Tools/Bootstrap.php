<?php

namespace CatPaw\Tools;

use Amp\ByteStream\IteratorStream;
use Amp\File\File;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\LazyPromise;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Loop;
use Amp\Producer;
use Amp\Promise;
use CatPaw\Attributes\Http\RequestHeader;
use CatPaw\Attributes\Sessions\Session;
use CatPaw\Attributes\StartWebServer;
use CatPaw\CatPaw;
use CatPaw\Configs\MainConfiguration;
use CatPaw\Attributes\AttributeLoader;
use CatPaw\Exceptions\InvalidByteRangeQueryException;
use CatPaw\Interfaces\ByteRangeWriterInterface;
use CatPaw\Services\ByteRangeService;
use CatPaw\Sessions\FileSystemSessionOperations;
use CatPaw\Tools\Helpers\Factory;
use CatPaw\Tools\Helpers\Route;
use Closure;
use Generator;
use Monolog\Logger;
use Parsedown;
use Psr\Log\LoggerInterface;
use ReflectionFunction;
use function Amp\ByteStream\getStdout;
use function Amp\File\exists;
use function Amp\File\getSize;
use function Amp\File\isDirectory;
use function Amp\File\openFile;


class Bootstrap {
	private static function check_entry_change(string $fileName, array &$store) {
		$size = filesize($fileName);
		$lastChange = filemtime($fileName);
		if(isset($store[$fileName]) && $store[$fileName]['lastChange'] < $lastChange)
			Loop::stop();
		else {
			$store[$fileName] = [
				'name'       => $fileName,
				'size'       => $size,
				'lastChange' => $lastChange,
			];
		}
	}

	private static function check_file_change(string $dirname, array &$store) {
		$files = [];
		Dir::findFilesRecursive($dirname, $files);

		foreach($files as $file) {
			$name = $file['name'];
			if(isset($store[$name])) {
				if($store[$name]['lastChange'] < $file['lastChange']) {
					Loop::stop();
					return;
				}
			}
			$store[$name] = $file;
		}
	}

	private static function dev(string $entryFileName, array $dirs, int $sleep) {
		$store = [];

		self::check_entry_change($entryFileName, $store);

		foreach($dirs as $dir)
			self::check_file_change($dir, $store);


		Loop::repeat($sleep, function() use ($dirs, $store, $entryFileName) {
			self::check_entry_change($entryFileName, $store);
			foreach($dirs as $dir)
				self::check_file_change($dir, $store);
		});
	}


	/**
	 * @param MainConfiguration  $config
	 * @param ReflectionFunction $main
	 *
	 * @return Promise
	 */
	private static function args(MainConfiguration $config, ReflectionFunction $main): Promise {
		return new LazyPromise(function() use ($config, $main) {
			$args = [];
			$i = 0;
			foreach($main->getParameters() as $parameter) {
				$args[$i] = yield Factory::create($parameter->getType()->getname(), false);
				$i++;
			}
			return $args;
		});
	}

	private static function markdown(MainConfiguration $config, string $filename): Promise {
		return new LazyPromise(function() use ($config, $filename) {
			//##############################################################
			$filenameLower = strtolower($config->httpWebroot.$filename);
			if(!str_ends_with($filenameLower, ".md"))
				return $config->httpWebroot.$filename;
			//##############################################################

			$filenameMD = "./.cache/markdown$filename.html";
			$filename = $config->httpWebroot.$filename;

			if(is_file($filenameMD))
				return $filenameMD;


			if(!is_dir($dirnameMD = dirname($filenameMD)))
				mkdir($dirnameMD, 0777, true);

			/** @var File $html */
			$html = yield openFile($filename, "r");

			$unsafe = !str_ends_with($filenameLower, ".unsafe.md");

			$chunkSize = 65536;
			$contents = '';

			while(!$html->eof()) {
				$chunk = yield $html->read($chunkSize);
				$contents .= $chunk;
			}
			yield $html->close();
			/** @var File $md */
			$md = yield openFile($filenameMD, "w");

			$config->mdp->setSafeMode($unsafe);
			$parsed = $config->mdp->parse($contents);

			yield $md->write($parsed);
			yield $md->close();

			return $filenameMD;
		});
	}

	private const MARKDOWN = 0;
	private const HTML     = 1;
	private const OTHER    = 2;

	/**
	 * @param MainConfiguration $config
	 * @param string            $filename
	 *
	 * @return Generator
	 */
	private static function init(MainConfiguration $config, string $filename): Generator {
		Route::notFound(function(
			#[RequestHeader("range")] false|array $range,
			Request $request,
			ByteRangeService $service,
		) use ($config) {
			$path = urldecode($request->getUri()->getPath());
			$filename = $config->httpWebroot.$path;
			if(yield isDirectory($filename)) {
				if(!str_ends_with($filename, '/'))
					$filename .= '/';

				if(yield exists("{$filename}index.md")) {
					$filename .= 'index.md';
				} else {
					$filename .= 'index.html';
				}
			}

			$lowered = strtolower($filename);

			if(str_ends_with($lowered, '.md'))
				$type = self::MARKDOWN;
			else if(str_ends_with($lowered, '.html') || str_ends_with($lowered, ".htm"))
				$type = self::HTML;
			else
				$type = self::OTHER;

			if(!strpos($filename, '../')) {
				if(self::MARKDOWN === $type) {
					/** @var string $filename */
					$filename = yield self::markdown($config, $filename);
				}
				$length = yield getSize($filename);
				try {
					return $service->response(
						rangeQuery: $range[0]??"",
						headers   : [
										"Content-Type"   => Mime::getContentType($filename),
										"Content-Length" => $length,
									],
						writer    : new class($filename) implements ByteRangeWriterInterface {
										private File $file;

										public function __construct(private string $filename) { }

										public function start(): Promise {
											return new LazyPromise(function() {
												$this->file = yield openFile($this->filename, "r");
											});
										}


										public function data(callable $emit, int $start, int $length): Promise {
											return new LazyPromise(function() use ($emit, $start, $length) {
												yield $this->file->seek($start);
												$data = yield $this->file->read($length);
												yield $emit($data);
											});
										}


										public function end(): Promise {
											return new LazyPromise(function() {
												yield $this->file->close();
											});
										}
									}
					);
				} catch(InvalidByteRangeQueryException) {
					return new Response(
						code          : Status::OK,
						headers       : [
											"accept-ranges"  => "bytes",
											"Content-Type"   => Mime::getContentType($filename),
											"Content-Length" => $length,
										],
						stringOrStream: new IteratorStream(
											new Producer(function($emit) use ($filename) {
												/** @var File $file */
												$file = yield openFile($filename, "r");
												while($chunk = yield $file->read(65536))
													yield $emit($chunk);
												yield $file->close();
											})
										)
					);
				}
			}
			return new Response(
				Status::NOT_FOUND,
				[],
				''
			);
		});

		if(is_file($filename)) {
			$filename = realpath($filename);
			$owd = getcwd();
			chdir(dirname($filename));
			require_once $filename;
			chdir($owd);
			/** @var mixed $result */
			if(!function_exists('main'))
				die(Strings::red("Please define a global main function.\n"));
			$main = new ReflectionFunction('main');
			if(yield StartWebServer::findByFunction($main))
				yield CatPaw::startWebServer($config);

			$args = yield self::args($config, $main);
			$result = $main->invoke(...$args);
			if($result instanceof Generator)
				yield from $result;
			else if($result instanceof Promise)
				yield $result;
		} else {
			die(Strings::red("Could not find php entry file \"$filename\".\n"));
		}
	}

	public static function start(string $filename, bool $dev = false, int $devSleep = 100, false|Closure $callback = false) {

		Session::setOperations(
			new FileSystemSessionOperations(
				ttl      : 1_440,
				dirname  : ".sessions",
				keepAlive: false,
			)
		);

		$config = new class($dev, $devSleep) extends MainConfiguration {
			public function __construct(bool $dev, int $devSleep) {
				$handler = new StreamHandler(getStdout());
				$handler->setFormatter(new ConsoleFormatter());
				$logger = new Logger('app');
				$logger->pushHandler($handler);

				$this->logger = $logger;
				Factory::setObject(Logger::class, $logger);
				Factory::setObject(LoggerInterface::class, $logger);

				$this->dev["enabled"] = $dev;
				$this->dev["sleep"] = $devSleep;
			}
		};

		Factory::setObject(MainConfiguration::class, $config);

		set_time_limit(0);
		ob_implicit_flush();
		ini_set('memory_limit', '-1');

		if(!$filename)
			die(Strings::red("Please point to a php entry file.\n"));

		$config->mdp = new Parsedown();

		Loop::run(function() use ($filename, $callback, $config) {
			$loader = new AttributeLoader();
			$loader->setLocation(getcwd());

			$dirs = [];
			$namespaces = $loader->getDefinedNamespaces();
			foreach($namespaces as $namespace => $locations) {
				$loader->loadModulesFromNamespace($namespace);
				yield $loader->loadClassesFromNamespace($namespace);
				$dirs = array_merge($dirs, $loader->getNamespaceDirectories($namespace));
			}

			if($config->dev["enabled"])
				self::dev(
					entryFileName: $filename,
					dirs         : $dirs,
					sleep        : $config->dev["sleep"]
				);


			yield from self::init($config, $filename);

			if($callback) {
				$result = $callback();
				if($result instanceof Promise)
					yield $result;
				else if($result instanceof Generator) {
					yield from $result;
				}
			}
		});
	}
}