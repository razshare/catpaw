<?php
namespace CatPaw\Web\Interfaces;

use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientHandler;

interface WebsocketInterface {
    /**
     * Handle the websocket connection by reading from and writing to it.
     * @param  WebsocketClientHandler $clientHandler
     * @return Websocket
     */
    public function success(WebsocketClientHandler $clientHandler):Websocket;
}