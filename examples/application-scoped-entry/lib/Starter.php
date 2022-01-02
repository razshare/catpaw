<?php
namespace Examples\ApplicationScopedEntry;
use Amp\LazyPromise;
use Amp\Promise;
use CatPaw\Attributes\ApplicationScoped;
use CatPaw\Attributes\Entry;
use Monolog\Logger;

#[ApplicationScoped]
class Starter {
	public function promise(): Promise {
		return new LazyPromise(function() {
			return "I'm a promise!";
		});
	}

	#[Entry]
	public function main(
		Logger $logger
	): Promise {
		return new LazyPromise(fn()=>$logger->info(yield $this->promise()));
	}

}