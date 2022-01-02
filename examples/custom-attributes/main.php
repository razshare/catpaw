<?php

namespace {

	use Amp\LazyPromise;
	use Amp\Promise;
	use CatPaw\Attributes\Http\Produces;
	use CatPaw\Attributes\Interfaces\AttributeInterface;
	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Attributes\Traits\CoreAttributeDefinition;
	use CatPaw\Http\HttpContext;
	use CatPaw\Tools\Helpers\Route;

	#[Attribute]
	class CustomHttpAttribute implements AttributeInterface {
		use CoreAttributeDefinition;

		public function __construct(private string $value) {
			echo "hello world\n";
		}

		public function onParameter(ReflectionParameter $parameter, mixed &$value, false|HttpContext $http): Promise {
			return new LazyPromise(function() use (
				$parameter,
				&$value,
				$http
			) {
				$value = "$this->value $value";
			});
		}
	}

	#[StartWebServer]
	function main() {
		Route::get("/", #[Produces("text/html")] function(
			#[CustomHttpAttribute("hello")] string $hello = 'world',
		) {
			return "$hello";
		});
	}
}