<?php

use CatPaw\Web\Server;

function main() {
    $server = Server::create();
    if ($server->error) {
        return $server;
    }

    $server->value->router->get("/{any}", static function() {
        return "hello world";
    });
    
    $server->value->start();
}