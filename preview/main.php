<?php
use function CatPaw\Core\anyError;
use function CatPaw\Core\asFileName;
use function CatPaw\Web\loadComponentsFromDirectory;
use CatPaw\Web\Server;

function main() {
    return anyError(function() {
        loadComponentsFromDirectory(asFileName(__DIR__, 'components'))->try();
        return Server::get()->withApiLocation(asFileName(__DIR__, 'api'))->start();
    });
}