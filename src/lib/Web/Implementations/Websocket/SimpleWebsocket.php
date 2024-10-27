<?php

namespace CatPaw\Web\Implementations\Websocket;

use Amp\Http\Server\HttpServer;
use Amp\Websocket\Server\Rfc6455Acceptor;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientHandler;
use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\None;
use CatPaw\Core\Result;
use CatPaw\Web\Interfaces\ServerInterface;
use CatPaw\Web\Interfaces\WebsocketInterface;
use Psr\Log\LoggerInterface;

#[Provider]
class SimpleWebsocket implements WebsocketInterface {
    private HttpServer $httpServer;
    public function __construct(
        private LoggerInterface $logger,
        private ServerInterface $server,
    ) {
    }

    /**
     * @return Result<None>
     */
    #[Entry] function start():Result {
        return $this->server->onStart(function(HttpServer $httpServer) {
            $this->httpServer = $httpServer;
        });
    }

    /**
     * Handle the websocket connection by reading from and writing to it.
     * @param  WebsocketClientHandler $clientHandler
     * @return Websocket
     */
    public function success(WebsocketClientHandler $clientHandler):Websocket {
        $acceptor = new Rfc6455Acceptor;
        return new Websocket($this->httpServer, $this->logger, $acceptor, $clientHandler);
    }
}
