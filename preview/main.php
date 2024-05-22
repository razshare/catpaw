<?php
use function CatPaw\Core\asFileName;
use function CatPaw\Core\error;

use CatPaw\Web\Server;
use CatPaw\Web\Services\ViewService;

function main(ViewService $view) {
    $view->loadComponentsFromDirectory(asFileName(__DIR__, './components'))->unwrap($error);
    if ($error) {
        return error($error);
    }

    return Server::get()
        ->withApiLocation(asFileName(__DIR__, './api'))
        ->start();
}