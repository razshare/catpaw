<?php
use function CatPaw\Core\error;
use CatPaw\Core\FileName;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Interfaces\ServerInterface;

class HelloController {
    public function get(string $name = 'world') {
        return "hello $name";
    }
}

function main(ServerInterface $server, RouterInterface $router) {
    $router->controller('/api/controllers/hello/{name}', HelloController::class)->unwrap($error);
    if ($error) {
        return error($error);
    }

    return $server
        ->withApiLocation(FileName::create(__DIR__, 'api'))
        ->start();
}