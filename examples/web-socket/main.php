<?php

namespace {

	use Amp\Websocket\Client;
	use Amp\Websocket\Message;
	use Amp\Websocket\Server\Gateway;
	use CatPaw\Attributes\StartWebServer;
	use CatPaw\Tools\Helpers\Route;
	use CatPaw\Tools\Helpers\WebSocketInterface;
	use Psr\Log\LoggerInterface;

	#[StartWebServer]
	function main() {
		Route::get(
			path    : "/",
			callback: function(
				LoggerInterface $logger
			) {
				return new class($logger) implements WebSocketInterface {

					public function __construct(private LoggerInterface $logger) { }

					public function onStart(Gateway $gateway) {
						// TODO: Implement onStart() method.
					}

					public function onMessage(Message $message, Gateway $gateway, Client $client): Generator {
						// TODO: Implement onMessage() method.
						$this->logger->info("Message:".(yield $message->read()));
					}

					public function onClose(...$args) {
						// TODO: Implement onClose() method.
					}

					public function onError(Throwable $e) {
						// TODO: Implement onError() method.
						$this->logger->error($e->getMessage());
					}
				};
			}
		);
	}
}