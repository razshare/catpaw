# Websockets

You can upgrade an http connection to websockets [RFC 6455](https://www.rfc-editor.org/rfc/rfc6455.html) using `websocket()`.

```php
// src/api/get.php
use Amp\Http\Server\Request;
use Amp\Websocket\Server\WebsocketClientHandler;
use function CatPaw\Web\websocket;

$handler = new class implements WebsocketClientHandler {
    public function handleClient(
        WebsocketClient $client,
        Request $request,
        Response $response,
    ):void {
        $client->sendText("welcome!");

        foreach ($client as $message) {
            echo $message.PHP_EOL;
        }
    }
}

return fn(Request $request) => websocket($request, $handler);
```
