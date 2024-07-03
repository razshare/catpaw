# Websockets

You can upgrade an http connection to websockets [RFC 6455](https://www.rfc-editor.org/rfc/rfc6455.html) using _websocket()_.

```php
namespace CatPaw\Web;
/**
* @param Request $request
* @param WebsocketClientHandler $handler
* @return ResponseModifier
 */
function websocket($request, $handler);
```

It's not completely automatic as it requires the incoming _Request_ as a dependency.

Other than that it's pretty straightforward

```php
<?php
use Amp\Http\Server\Request;
use function CatPaw\Web\websocket;

return fn(Request $request) => websocket(
    request: $request,
    handler: new class implements WebsocketClientHandler {
        public function handleClient(
            WebsocketClient $client,
            Request $request,
            Response $response,
        ): void {
            $client->sendText("welcome!");

            foreach ($client as $message) {
                echo $message.PHP_EOL;
            }
        }
    }
);
```
