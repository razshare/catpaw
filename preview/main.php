<?php
use CatPaw\Web\Interfaces\ServerInterface;
function main(ServerInterface $server) {
    return $server->start();
}