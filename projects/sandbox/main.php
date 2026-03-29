<?php
use function CatPaw\Core\error;
use CatPaw\Core\FileName;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Interfaces\ServerInterface;

class DefaultController {
    public function get(): string {
        return "hello from default controller";
    }
}

function main(ServerInterface $server, RouterInterface $router) {
    $router->addController('/', DefaultController::class)->unwrap($error);
    if ($error) {
        return error($error);
    }

    return $server
        ->withApiLocation(FileName::create(__DIR__, 'api'))
        ->start();
}