<?php

namespace CatPaw;

use Amp\LazyPromise;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Loop;
use Amp\Promise;
use CatPaw\Attribute\AttributeLoader;
use CatPaw\Utility\Dir;
use CatPaw\Utility\Factory;
use CatPaw\Utility\Strings;
use Closure;
use Generator;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ReflectionFunction;
use function Amp\ByteStream\getStdout;

class Bootstrap {
	private static function checkEntryChange(string $fileName, array &$store) {
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

	private static function checkFileChange(string $dirname, array &$store) {
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

		self::checkEntryChange($entryFileName, $store);

		foreach($dirs as $dir)
			self::checkFileChange($dir, $store);


		Loop::repeat($sleep, function() use ($dirs, $store, $entryFileName) {
			self::checkEntryChange($entryFileName, $store);
			foreach($dirs as $dir)
				self::checkFileChange($dir, $store);
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
			yield Factory::dependencies($main, $args, false);
			return $args;
		});
	}

	/**
	 * @param MainConfiguration $config
	 * @param string            $filename
	 *
	 * @return Generator
	 */
	private static function init(MainConfiguration $config, string $filename): Generator {
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