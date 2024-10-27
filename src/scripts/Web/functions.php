<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientHandler;

use CatPaw\Core\Container;
use function CatPaw\Core\error;

use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Web\Interfaces\WebsocketInterface;
use Psr\Http\Message\UriInterface;

/**
 * Suggest a download action to the client.
 * @param  Response $response
 * @return Response
 */
function download(Response $response):Response {
    $response->setHeader("application/octet-stream", '');
    return $response;
}

/**
 *
 * @param  string                          $to
 * @return SuccessResponseModifier<string>
 */
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
 * @template T
 * @param  T                          $data
 * @param  int                        $status
 * @param  array<string,string>       $headers
 * @param  false|string               $message
 * @return SuccessResponseModifier<T>
 */
function success(
    mixed $data = '',
    false|int $status = false,
    array $headers = [],
    false|string $message = false,
):SuccessResponseModifier {
    if (false === $message) {
        $message = HttpStatus::reason($status);
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
 * @param  array<string,string>  $headers
 * @return ErrorResponseModifier
 */
function failure(
    false|string $message = false,
    int $status = INTERNAL_SERVER_ERROR,
    array $headers = []
):ErrorResponseModifier {
    if (false === $message) {
        $message = HttpStatus::reason($status);
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
    $queryString = $uri->getQuery();
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
 *  handler: new class implements WebsocketClientHandler {
 *      public function handleClient(WebsocketClient $client): void {
 *          $client->sendText("hello!");
 *          foreach ($client as $message) {
 *              echo "client says: $message\n";
 *          }
 *      }
 *  }
 * );
 * ```
 * @param  WebsocketClientHandler $handler Websocket handler.
 * @return Result<Websocket>
 */
function websocket(WebsocketClientHandler $handler): Result {
    $websocket = Container::get(WebsocketInterface::class)->unwrap($error);
    if ($error) {
        return error($error);
    }

    return ok($websocket->success($handler));
}