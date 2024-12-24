<?php
namespace Tests;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use function CatPaw\Core\anyError;
use CatPaw\Core\Container;
use CatPaw\Core\FileName;
use CatPaw\Core\Signal;
use const CatPaw\Web\APPLICATION_JSON;
use const CatPaw\Web\APPLICATION_XML;
use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Interfaces\RouterInterface;
use CatPaw\Web\Interfaces\ServerInterface;
use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_HTML;
use const CatPaw\Web\TEXT_PLAIN;
use function json_decode;
use PHPUnit\Framework\TestCase;
use Tests\Types\SchemaConsumeSomething;

class WebTest extends TestCase {
    public function testAll():void {
        Container::requireLibraries(FileName::create(__DIR__, '../src/lib'))->unwrap($error);
        $this->assertNull($error);
        Container::loadDefaultProviders("Test")->unwrap($error);
        $this->assertNull($error);
        $server = Container::get(ServerInterface::class)->unwrap($error);
        $this->assertNull($error);
        $server
            ->withInterface('127.0.0.1:5858')
            ->withApiLocation(FileName::create(__DIR__, './Api'))
            ->withStaticsLocation(FileName::create(__DIR__, './www'))
            ->withApiPrefix('Api');

        $readySignal = Signal::create();

        $readySignal->listen(function() use ($server) {
            anyError(function() {
                yield Container::run($this->makeSureParamsAndQueriesWork(...));
                yield Container::run($this->makeSureSessionWorks(...));
                yield Container::run($this->makeSureXmlConversionWorks(...));
                yield Container::run($this->makeSureJsonConversionWorks(...));
                yield Container::run($this->makeSureProducesHintsWork(...));
                yield Container::run($this->makeSureFallbackContentWorks(...));
                yield Container::run($this->makeSureContentNegotiationWorks(...));
                yield Container::run($this->makeSureParamHintsWork(...));
                yield Container::run($this->makeSureOpenApiDataIsGeneratedCorrectly(...));
            })->unwrap($error);
            if ($error) {
                $this->assertNull($error);
                $server->stop()->unwrap($error);
                $this->assertNull($error);
            } else {
                $server->stop()->unwrap($error);
                $this->assertNull($error);
            }
        });

        $server->start($readySignal)->unwrap($error);
        $this->assertNull($error);
    }

    public function makeSureParamsAndQueriesWork(HttpClient $http):void {
        $request  = new Request("http://127.0.0.1:5858/Api/ParamsAndQueries/asd?lastName=fgh", "GET");
        $response = $http->request($request);
        $actual   = $response->getBody()->buffer();
        $this->assertEquals('your name is asd fgh', $actual);
    }

    public function makeSureSessionWorks(HttpClient $http):void {
        $request  = new Request("http://127.0.0.1:5858/Api/Session", "GET");
        $response = $http->request($request);
        $actual   = $response->getBody()->buffer();
        $this->assertEquals('0', $actual);
        $header = $response->getHeader('set-cookie') ?? '';
        $this->assertNotEmpty($header);
        $this->assertNotFalse(preg_match('/=([\w-]+);?/', $header, $matches));
        $id = $matches[1];
        $this->assertNotEmpty($id);

        $request = new Request("http://127.0.0.1:5858/Api/Session", "GET");
        $request->setHeader('cookie', "session-id=$id");
        $response = $http->request($request);
        $actual   = $response->getBody()->buffer();
        $this->assertEquals('1', $actual);
    }

    private function makeSureXmlConversionWorks(HttpClient $http):void {
        $request = new Request("http://127.0.0.1:5858/Api/Object/user1", "GET");
        $request->setHeader("Accept", APPLICATION_XML);
        $response          = $http->request($request);
        $actualContentType = $response->getHeader("Content-Type");
        $this->assertEquals(APPLICATION_XML, $actualContentType);
        $actualBody = $response->getBody()->buffer();
        $this->assertStringStartsWith("<?xml version=\"1.0\"", $actualBody);
    }

    private function makeSureJsonConversionWorks(HttpClient $http):void {
        $request = new Request("http://127.0.0.1:5858/Api/Object/user1", "GET");
        $request->setHeader("Accept", APPLICATION_JSON);
        $response          = $http->request($request);
        $actualContentType = $response->getHeader("Content-Type");
        $this->assertEquals(APPLICATION_JSON, $actualContentType);
        $actualBody = $response->getBody()->buffer();
        $this->assertJson($actualBody);
    }

    private function makeSureProducesHintsWork(RouterInterface $router):void {
        $api     = $router->findRoute('GET', '/Api');
        $apiName = $router->findRoute('GET', '/Api/{name}');

        $this->assertTrue((bool)$api);
        $this->assertTrue((bool)$apiName);

        $this->assertNotEmpty($api->produces);
        $this->assertNotEmpty($apiName->produces);

        foreach ($api->produces as $produces) {
            $this->assertContains(TEXT_PLAIN, $produces->contentType());
        }

        foreach ($apiName->produces as $produces) {
            $this->assertContains(TEXT_HTML, $produces->contentType());
        }
    }

    private function makeSureFallbackContentWorks(HttpClient $http):void {
        $response1 = $http->request(new Request("http://127.0.0.1:5858/Api/Fallback", "GET"));
        $actual    = $response1->getBody()->buffer();
        $this->assertEquals("hello world", $actual);
        $this->assertEquals("text/plain", $response1->getHeader("Content-Type"));

        $response2 = $http->request(new Request("http://127.0.0.1:5858/Api/Fallback/", "GET"));
        $actual    = $response2->getBody()->buffer();
        $this->assertEquals("hello world", $actual);
        $this->assertEquals("text/plain", $response2->getHeader("Content-Type"));

        $response2 = $http->request(new Request("http://127.0.0.1:5858/Api/Fallback/test", "GET"));
        $actual    = $response2->getBody()->buffer();
        $this->assertEquals("hello test", $actual);
        $this->assertEquals("text/plain", $response2->getHeader("Content-Type"));
    }

    private function makeSureContentNegotiationWorks(HttpClient $http):void {
        $response1 = $http->request(new Request("http://127.0.0.1:5858/Api", "GET"));
        $actual    = $response1->getBody()->buffer();
        $this->assertEquals("root", $actual);
        $this->assertEquals("text/plain", $response1->getHeader("Content-Type"));

        $response2 = $http->request(new Request("http://127.0.0.1:5858/Api/test", "GET"));
        $actual    = $response2->getBody()->buffer();
        $this->assertEquals("test", $actual);
        $this->assertEquals("text/html", $response2->getHeader("Content-Type"));
    }

    private function makeSureParamHintsWork(RouterInterface $router, HttpClient $http):void {
        $router->get("/GetWithParams/{name}", fn (#[Param] string $name) => success("hello $name"));
        $response = $http->request(new Request("http://127.0.0.1:5858/GetWithParams/user1"));
        $this->assertEquals("hello user1", $response->getBody()->buffer());
        $response = $http->request(new Request("http://127.0.0.1:5858/GetWithParams/user2"));
        $this->assertEquals("hello user2", $response->getBody()->buffer());
    }

    private function makeSureOpenApiDataIsGeneratedCorrectly(HttpClient $http):void {
        $response = $http->request(new Request("http://127.0.0.1:5858/Api/OpenApi", "GET"));
        $text     = $response->getBody()->buffer();
        $json     = json_decode($text, true);
        $this->assertNotEmpty($text);
        $this->assertArrayHasKey('openapi', $json);
        $this->assertArrayHasKey('info', $json);
        $this->assertArrayHasKey('paths', $json);
        $this->assertArrayHasKey('/Api/{name}', $json['paths']);
        $this->assertArrayHasKey('get', $json['paths']['/Api/{name}']);
        $this->assertArrayHasKey('summary', $json['paths']['/Api/{name}']['get']);

        $this->assertArrayHasKey('/Api/ConsumeSomething', $json['paths']);
        $this->assertArrayHasKey('post', $json['paths']['/Api/ConsumeSomething']);
        $this->assertArrayHasKey('requestBody', $json['paths']['/Api/ConsumeSomething']['post']);
        $this->assertArrayHasKey('content', $json['paths']['/Api/ConsumeSomething']['post']['requestBody']);
        $this->assertArrayHasKey(APPLICATION_JSON, $json['paths']['/Api/ConsumeSomething']['post']['requestBody']['content']);
        $this->assertArrayHasKey('schema', $json['paths']['/Api/ConsumeSomething']['post']['requestBody']['content'][APPLICATION_JSON]);
        $this->assertArrayHasKey('$ref', $json['paths']['/Api/ConsumeSomething']['post']['requestBody']['content'][APPLICATION_JSON]['schema']);
        $this->assertArrayHasKey('key1', $json['components']['schemas'][SchemaConsumeSomething::class]['properties']);
        $this->assertArrayHasKey('key2', $json['components']['schemas'][SchemaConsumeSomething::class]['properties']);
        $this->assertArrayHasKey('key3', $json['components']['schemas'][SchemaConsumeSomething::class]['properties']);
        $this->assertArrayHasKey('key4', $json['components']['schemas'][SchemaConsumeSomething::class]['properties']);
    }
}
