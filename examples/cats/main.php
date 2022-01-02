<?php

namespace {

	use CatPaw\Attributes\Http\Consumes;
	use CatPaw\Attributes\Http\Produces;
	use CatPaw\Attributes\Http\RequestBody;
	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Tools\Helpers\Route;

	#[StartWebServer]
	function main(){

		$cats = [];

		Route::get(
			path    : "/cats",
			callback:
			#[Produces("application/json")]
			function() use ($cats) {
				return $cats;
			}
		);

		Route::post(
			path    : "/cats",
			callback:
			#[Consumes("application/json")]
			function(
				#[RequestBody]
				array $cat
			) use(&$cats) {
				$cats[] = $cat;
			}
		);

	}
}
