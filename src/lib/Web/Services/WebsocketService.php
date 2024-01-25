<?php

namespace CatPaw\Web\Services;

use Amp\Http\Server\HttpServer;
use Amp\Websocket\Server\Rfc6455Acceptor;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientHandler;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;

use function CatPaw\Core\error;

use function CatPaw\Core\ok;

use CatPaw\Web\Server;
use Psr\Log\LoggerInterface;

#[Service]
class WebsocketService {
    private HttpServer $httpServer;
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[Entry] public function start() {
        Server::onStart(function() {
            $server = Server::get()->try($error);
            if ($error) {
                return error($error);
            }
            $this->httpServer = $server->getHttpServer()->try($error);
            if ($error) {
                return error($error);
            }
            return ok();
        });
    }

    public function create(WebsocketClientHandler $clientHandler):Websocket {
        $acceptor = new Rfc6455Acceptor;
        return new Websocket($this->httpServer, $this->logger, $acceptor, $clientHandler);
    }
}
