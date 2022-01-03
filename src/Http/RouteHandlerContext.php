<?php

namespace CatPaw\Http;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use CatPaw\Sessions\SessionOperationsInterface;

class RouteHandlerContext {
	public string $method;
	public string $path;
	public bool   $isFilter;
}