<?php

namespace CatPaw\Web\Services;

use Amp\Http\Server\HttpServer;
use Amp\Websocket\Server\Rfc6455Acceptor;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientHandler;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\None;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Server;
use Psr\Log\LoggerInterface;

#[Service]
class WebsocketService {
    private HttpServer $httpServer;
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return Unsafe<None>
     */
    #[Entry] function start():Unsafe {
        return Server::onStart(function(HttpServer $httpServer) {
            $this->httpServer = $httpServer;
        });
    }

    public function create(WebsocketClientHandler $clientHandler):Websocket {
        $acceptor = new Rfc6455Acceptor;
        return new Websocket($this->httpServer, $this->logger, $acceptor, $clientHandler);
    }
}
