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
	class CustomHttpParameterAttribute implements AttributeInterface {
		use CoreAttributeDefinition;

		public function __construct(private string $value) {
			echo "hello world\n";
		}

		public function onParameter(ReflectionParameter $reflection, mixed &$value, false|HttpContext $http): Promise {
			return new LazyPromise(function() use (
				$reflection,
				&$value,
				$http
			) {
				$value = "$this->value $value";
			});
		}
	}

	#[Attribute]
	class CustomRouteAttribute implements AttributeInterface {
		use CoreAttributeDefinition;

		public function onRouteHandler(ReflectionFunction $reflection, Closure &$value, bool $isFilter): Promise {
			return new LazyPromise(function() use ($reflection) {

			});
		}
	}

	#[StartWebServer]
	function main() {
		Route::get(
			path    : "/",
			callback: #[Produces("text/html")]
			#[CustomRouteAttribute]
			function(
				#[CustomHttpParameterAttribute("hello")] string $name = 'world',
			) {
				return $name;
			}
		);
	}
}