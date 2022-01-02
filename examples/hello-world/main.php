<?php

namespace {

	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Tools\Helpers\Route;

	#[StartWebServer]
	function main() {
		Route::get("/", fn() => "hello world");
	}
}