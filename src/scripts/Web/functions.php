<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\WebsocketClient;

use function CatPaw\Core\asFileName;
use CatPaw\Core\Container;
use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Services\WebsocketService;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Suggest a download action to the client.
 * @param  Response $response
 * @return Response
 */
function download(Response $response):Response {
    $response->setHeader("application/octet-stream", '');
    return $response;
}

function redirect(string $to):SuccessResponseModifier {
    return success(
        status: HttpStatus::FOUND,
        headers: [
            'Location' => $to,
        ]
    );
}

/**
 * Success response.
 * @param  mixed                   $data
 * @param  int                     $status
 * @param  array                   $headers
 * @param  false|string            $message
 * @return SuccessResponseModifier
 */
function success(
    mixed $data = '',
    false|int $status = false,
    array $headers = [],
    false|string $message = false,
):SuccessResponseModifier {
    if (false === $message) {
        $message = HttpStatus::getReason($status);
    }
    return SuccessResponseModifier::create(
        data   : $data,
        headers: $headers,
        status : $status,
        message: $message,
    );
}

/**
 * Bad request.
 * @param  string                $message
 * @return ErrorResponseModifier
 */
function badRequest(string $message):ErrorResponseModifier {
    return failure($message, BAD_REQUEST);
}

/**
 * Something is wrong, notify the client with a code and a message.
 * @param  false|string          $message
 * @param  int                   $status
 * @param  array                 $headers
 * @return ErrorResponseModifier
 */
function failure(
    false|string $message = false,
    int $status = INTERNAL_SERVER_ERROR,
    array $headers = []
):ErrorResponseModifier {
    if (false === $message) {
        $message = HttpStatus::getReason($status);
    }
    return ErrorResponseModifier::create(
        status: $status,
        message: $message,
        headers: $headers,
    );
}

/**
 * Get a list of `key => value` pairs for each query string.
 * @param  UriInterface                        $uri
 * @return array<string,string|bool|int|float>
 */
function queries(UriInterface $uri):array {
    /** @var array<string,string|bool|int|float> $queries */
    $queries     = [];
    $queryString = $uri->getQuery() ?? '';
    foreach (explode('&', $queryString) as $option) {
        $result = explode('=', $option, 2);
        $key    = $result[0] ?? '';
        if ('' === $key) {
            continue;
        }
        $key   = urldecode($key);
        $value = $result[1] ?? true;
        if (true !== $value) {
            $value = urldecode($value);
        }
        if (is_numeric($value)) {
            if (str_contains($value, '.')) {
                $value = (float)$value;
            } else {
                $value = (int)$value;
            }
        }
        $queries[$key] = $value;
    }
    return $queries;
}

/**
 * Return this from a route handler to upgrade an http connection to websocket [RFC 6455](https://www.rfc-editor.org/rfc/rfc6455.html).
 *
 * ## Example
 * ```php
 * return fn() => websocket(
 *  request: $request,
 *  handler: new class implements WebsocketClientHandler {
 *      public function handleClient(
 *          WebsocketClient $client,
 *          Request $request,
 *          Response $response,
 *      ): void {
 *          $client->sendText("hello!");
 *          foreach ($client as $message) {
 *              echo "client says: $message\n";
 *          }
 *      }
 *  }
 * );
 * ```
 * @param  Request                $request Incoming http request.
 * @param  WebsocketClientHandler $handler Websocket handler.
 * @return ResponseModifier
 */
function websocket(Request $request, WebsocketClientHandler $handler): ResponseModifier {
    $websocketService = Container::create(WebsocketService::class)->try($errorWebsocket);
    if ($errorWebsocket) {
        $logger = Container::create(LoggerInterface::class)->try($errorLogger);
        if ($errorLogger) {
            echo $errorLogger.PHP_EOL;
            echo $errorWebsocket.PHP_EOL;
        }
        $logger->error($errorWebsocket);
        return failure();
    }
    return success($websocketService->create($handler)->handleRequest($request));
}

/**
 * Render twig a file.
 * @param  string            ...$name Path to the twig file.
 * @return TwigRenderContext
 */
function twig(string ...$name):TwigRenderContext {
    return TwigRenderContext::create(asFileName(...$name));
}
