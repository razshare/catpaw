<?php

namespace {

	use Amp\Http\Server\Response;
	use Amp\Http\Status;
	use CatPaw\Attributes\Http\PathParam;
	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Tools\Helpers\Route;


	#[StartWebServer]
	function main() {

		$filter1 = fn(#[PathParam] int $value) => $value > 0 ? true : new Response(Status::BAD_REQUEST, [], "Bad request :/");

		Route::get(
			path    : "/{value}",
			callback: [
						  $filter1,
						  fn(#[PathParam] int $value) => $value,
					  ]
		);
	}
}
