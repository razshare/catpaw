<?php

namespace {

	use Amp\Http\Server\Response;
	use Amp\Http\Status;
	use CatPaw\Attributes\Http\Produces;
	use CatPaw\Attributes\Sessions\Session;
	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Tools\Helpers\Route;

	#[StartWebServer]
	function main() {
		Route::get("/",
			#[Produces("text/html")]
			function(
				 #[Session]
				array &$session,
			) {
				if(!isset($session['created']))
					$session['created'] = time();

				$contents = print_r($session, true);

				return <<<HTML
					this is my session <br /><pre>$contents</pre>
				HTML;
			}
		);
	}
}