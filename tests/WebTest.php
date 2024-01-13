<?php
namespace Tests;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use function CatPaw\Core\anyError;
use CatPaw\Core\Container;
use CatPaw\Core\Signal;
use const CatPaw\Web\APPLICATION_JSON;
use const CatPaw\Web\APPLICATION_XML;
use CatPaw\Web\Attributes\Param;
use CatPaw\Web\Server;
use const CatPaw\Web\TEXT_HTML;
use const CatPaw\Web\TEXT_PLAIN;
use function json_decode;
use PHPUnit\Framework\TestCase;

class WebTest extends TestCase {
    public function testAll() {
        Container::load('./src/lib/')->try($error);
        $this->assertFalse($error);
        Container::set(HttpClient::class, HttpClientBuilder::buildDefault());
        $server = Server::create(
            interface: '127.0.0.1:8000',
            api      : 'tests/api',
            www      : 'tests/www',
            apiPrefix: 'api'
        )->try($error);
        $this->assertFalse($error);

        Container::set(Server::class, $server);

        $readySignal = Signal::create();

        $readySignal->listen(function() use ($server) {
            anyError(function() {
                yield Container::run($this->makeSureXmlConversionWorks(...));
                yield Container::run($this->makeSureJsonConversionWorks(...));
                yield Container::run($this->makeSureProducesHintsWork(...));
                yield Container::run($this->makeSureContentNegotiationWorks(...));
                yield Container::run($this->makeSureParamHintsWork(...));
                yield Container::run($this->makeSureOpenApiDataIsGeneratedCorrectly(...));
            })->try($error);
            $this->assertFalse($error);
            $server->stop()->try($error);
            $this->assertFalse($error);
        });

        $server->start($readySignal)->await()->try($error);
        $this->assertFalse($error);
    }


    /**
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    private function makeSureXmlConversionWorks(HttpClient $http): void {
        $request = new Request("http://127.0.0.1:8000/api/object/user1", "GET");
        $request->setHeader("Accept", APPLICATION_XML);
        $response          = $http->request($request);
        $actualContentType = $response->getHeader("Content-Type");
        $this->assertEquals(APPLICATION_XML, $actualContentType);
        $actualBody = $response->getBody()->buffer();
        $this->assertStringStartsWith("<?xml version=\"1.0\"", $actualBody);
    }

    /**
     * @throws BufferException
     * @throws StreamException
     * @throws HttpException
     */
    private function makeSureJsonConversionWorks(HttpClient $http): void {
        $request = new Request("http://127.0.0.1:8000/api/object/user1", "GET");
        $request->setHeader("Accept", APPLICATION_JSON);
        $response          = $http->request($request);
        $actualContentType = $response->getHeader("Content-Type");
        $this->assertEquals(APPLICATION_JSON, $actualContentType);
        $actualBody = $response->getBody()->buffer();
        $this->assertJson($actualBody);
    }

    private function makeSureProducesHintsWork(Server $server): void {
        $api         = $server->router->findRoute('GET', '/api');
        $apiUsername = $server->router->findRoute('GET', '/api/{username}');

        $this->assertTrue((bool)$api);
        $this->assertTrue((bool)$apiUsername);
        $this->assertContains(TEXT_PLAIN, $api->produces->getContentType());
        $this->assertContains(TEXT_HTML, $apiUsername->produces->getContentType());
    }

    /**
     * @param  HttpClient      $http
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    private function makeSureContentNegotiationWorks(HttpClient $http): void {
        $response1 = $http->request(new Request("http://127.0.0.1:8000/api", "GET"));
        $actual    = $response1->getBody()->buffer();
        $this->assertEquals("hello", $actual);
        $this->assertEquals("text/plain", $response1->getHeader("Content-Type"));

        $response2 = $http->request(new Request("http://127.0.0.1:8000/api/world", "GET"));
        $actual    = $response2->getBody()->buffer();
        $this->assertEquals("hello world", $actual);
        $this->assertEquals("text/html", $response2->getHeader("Content-Type"));
    }

    /**
     * @throws HttpException
     * @throws BufferException
     * @throws StreamException
     */
    private function makeSureParamHintsWork(Server $server, HttpClient $http): void {
        $server->router->get("/get-with-params/{name}", fn (#[Param] string $name) => "hello $name");
        $response = $http->request(new Request("http://127.0.0.1:8000/get-with-params/user1"));
        $this->assertEquals("hello user1", $response->getBody()->buffer());
        $response = $http->request(new Request("http://127.0.0.1:8000/get-with-params/user2"));
        $this->assertEquals("hello user2", $response->getBody()->buffer());
    }

    /**
     * @param  HttpClient      $http
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    private function makeSureOpenApiDataIsGeneratedCorrectly(HttpClient $http): void {
        $response = $http->request(new Request("http://127.0.0.1:8000/api/openapi", "GET"));
        $text     = $response->getBody()->buffer();
        $json     = json_decode($text, true);
        $this->assertNotEmpty($text);
        $this->assertArrayHasKey('openapi', $json);
        $this->assertArrayHasKey('info', $json);
        $this->assertArrayHasKey('paths', $json);
        $this->assertArrayHasKey('/api/{username}', $json['paths']);
        $this->assertArrayHasKey('get', $json['paths']['/api/{username}']);
        $this->assertArrayHasKey('summary', $json['paths']['/api/{username}']['get']);

        $this->assertArrayHasKey('/api/consume-something', $json['paths']);
        $this->assertArrayHasKey('post', $json['paths']['/api/consume-something']);
        $this->assertArrayHasKey('requestBody', $json['paths']['/api/consume-something']['post']);
        $this->assertArrayHasKey('content', $json['paths']['/api/consume-something']['post']['requestBody']);
        $this->assertArrayHasKey(APPLICATION_JSON, $json['paths']['/api/consume-something']['post']['requestBody']['content']);
        $this->assertArrayHasKey('schema', $json['paths']['/api/consume-something']['post']['requestBody']['content'][APPLICATION_JSON]);
        $this->assertArrayHasKey('$ref', $json['paths']['/api/consume-something']['post']['requestBody']['content'][APPLICATION_JSON]['schema']);
        $this->assertArrayHasKey('key1', $json['components']['schemas']['SchemaConsumeSomething']['properties']);
        $this->assertArrayHasKey('key2', $json['components']['schemas']['SchemaConsumeSomething']['properties']);
        $this->assertArrayHasKey('key3', $json['components']['schemas']['SchemaConsumeSomething']['properties']);
        $this->assertArrayHasKey('key4', $json['components']['schemas']['SchemaConsumeSomething']['properties']);
    }
}
