<?php

namespace {

	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Tools\Helpers\Route;
	use function Examples\Modules\test;

	#[StartWebServer]
	function main() {
		Route::get("/",function(){
			return test();
		});
	}
}