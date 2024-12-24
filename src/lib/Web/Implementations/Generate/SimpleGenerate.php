<?php
namespace CatPaw\Web\Implementations\Generate;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Directory;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\FileName;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Core\Signal;
use CatPaw\Web\Interfaces\GenerateInterface;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Interfaces\ServerInterface;
use Psr\Log\LoggerInterface;

#[Provider]
class SimpleGenerate implements GenerateInterface {
    public function __construct(
        private ServerInterface $server,
        private RouterInterface $router,
        private LoggerInterface $logger,
    ) {
    }

    public function generate(string $interface = '127.0.0.1:8080', string $outputDirectory = 'generated'):Result {
        $this->server->withInterface($interface);
        $baseUrl = "http://$interface";
        $signal  = Signal::create();
        $signal->listen(function() use ($baseUrl, $outputDirectory) {
            $context = $this->router->getContext();
            $routes  = $context->findAllRoutes();
            $client  = HttpClientBuilder::buildDefault();
            foreach ($routes as $routeItems) {
                foreach ($routeItems as $route) {
                    $request  = new Request("$baseUrl$route->symbolicPath", $route->symbolicMethod);
                    $response = $client->request($request);
                    $contents = $response->getBody()->buffer();
                    $fileName = FileName::create($outputDirectory)->withoutPhar();
                    Directory::create($fileName)->unwrap($error);
                    if ($error) {
                        $this->logger->error($error);
                        continue;
                    }
                    $path = str_replace($baseUrl, '', $route->symbolicPath);
                    File::writeFile("$fileName$path", $contents)->unwrap($error);
                    if ($error) {
                        $this->logger->error($error);
                        continue;
                    }
                }
            }
            $this->server->stop();
        });
        $this->server->start($signal)->unwrap($error);
        if ($error) {
            return error($error);
        }
        return ok();
    }
}