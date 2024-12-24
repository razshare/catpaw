<?php
use CatPaw\Core\FileName;
use CatPaw\Web\Interfaces\ServerInterface;

function main(ServerInterface $server) {
    return $server
        ->withApiLocation(FileName::create(__DIR__, 'api'))
        ->start();
}