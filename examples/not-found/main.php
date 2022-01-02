<?php

namespace {

	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Tools\Helpers\Route;

	#[StartWebServer]
	function main() {
		Route::get("@404", function() {
			return "Sorry, couldn't find the resource!";
		});
	}
}