<?php
use function CatPaw\Core\asFileName;
use CatPaw\Web\Server;

function main() {
    return Server::get()
        ->withApiLocation(asFileName(__DIR__, './api'))
        ->start();
}