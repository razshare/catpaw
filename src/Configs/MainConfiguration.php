<?php

namespace CatPaw\Configs;

use Amp\Socket\Certificate;
use CatPaw\Attributes\Sessions\Session;
use CatPaw\Sessions\SessionOperationsInterface;
use Closure;
use JetBrains\PhpStorm\ArrayShape;
use Monolog\Logger;
use Parsedown;
use SessionHandlerInterface;

abstract class MainConfiguration {

	/** @var string[]|string List of interfaces to bind to. */
	public array|string $httpInterfaces = "127.0.0.1:8080";

	/** @var string[]|string List of secure interfaces to bind to (requires pemCertificate). */
	public array|string|false $httpSecureInterfaces = false;

	/** @var string Directory the application should serve. */
	public string $httpWebroot = 'public';

	/** @var false|Certificate Socket certificate to use for secure connections. */
	public false|Certificate $pemCertificate = false;

	/** @var false|Logger Application logger. */
	public false|Logger $logger = false;

	/** @var bool This dictates if the stack trace should be shown to the client whenever an Exception is caught or not. */
	public bool $httpShowStackTrace = false;

	/** @var bool This dictates if exceptions should be shown to the client whenever an Exception is caught or not. */
	public bool $httpShowException = false;

	/** @var false|Closure Will be executed just before the server starts. */
	public false|Closure $beforeStart = false;

	/** @var Parsedown Markdown parser */
	public Parsedown $mdp;

	#[ArrayShape([
		"enabled" => "bool",
		"sleep"   => "int",
	])]
	public array $dev = [
		"enabled" => false,
		"sleep"   => 100,
	];
}
