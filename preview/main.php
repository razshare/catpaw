<?php

use function CatPaw\anyError;
use function CatPaw\ok;
use CatPaw\Web\FileServer;

use CatPaw\Web\Server;
use CatPaw\Web\Services\OpenApiService;

function main() {
    return anyError(
        $server = Server::create(apiPrefix:'api'),
        $server->value->router->get("/index", static fn () => "hello world"),
        $server->value->router->get("/openapi", static fn (OpenApiService $openApiService) => $openApiService->getData()),
        $fileServer = FileServer::create($server->value),
        ok($server->value->setFileServer($fileServer->value)),
        ok($server->value->start()),
    );
}