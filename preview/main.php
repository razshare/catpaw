<?php
use function CatPaw\Core\asFileName;

use CatPaw\Web\Interfaces\ServerInterface;

function main(ServerInterface $server) {
    return $server
        ->withDocumentsLocation(asFileName(__DIR__, './documents'))
        ->withApiLocation(asFileName(__DIR__, './api'))
        ->start();
}