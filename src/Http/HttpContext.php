<?php

namespace CatPaw\Http;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use CatPaw\Sessions\SessionOperationsInterface;

class HttpContext {
	public SessionOperationsInterface $sessionOperations;

	public string   $eventID;
	public array    $params;
	public array    $query;
	public Request  $request;
	public Response $response;
}