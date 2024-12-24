<?php
use function CatPaw\Core\asFileName;
use CatPaw\Web\Interfaces\ServerInterface;

function main(ServerInterface $server) {
    return $server
        ->withApiLocation(asFileName(__DIR__, 'api'))
        ->start();
}