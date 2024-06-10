<?php
use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use CatPaw\Web\Interfaces\ServerInterface;

use function CatPaw\Web\loadComponentsFromDirectory;

function main(ServerInterface $server) {
    return anyError(function() use ($server) {
        loadComponentsFromDirectory(asFileName(__DIR__, 'components'))->try();
        return $server->withApiLocation(asFileName(__DIR__, 'api'))->start();
    });
}