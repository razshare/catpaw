<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Websocket\Server\Websocket;
use Amp\Websocket\Server\WebsocketClientHandler;

use CatPaw\Core\Container;
use function CatPaw\Core\error;

use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Implementations\View\LatteView;
use CatPaw\Web\Interfaces\ViewEngineInterface;
use CatPaw\Web\Interfaces\ViewInterface;
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
 * @param  array<string,string>  $headers
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
 * @return Unsafe<Websocket>
 */
function websocket(WebsocketClientHandler $handler): Unsafe {
    $websocket = Container::get(WebsocketInterface::class)->unwrap($error);
    if ($error) {
        return error($error);
    }

    return ok($websocket->success($handler));
}

function view():ViewInterface {
    return new LatteView;
}

/**
 * 
 * @param  string       $directoryName
 * @return Unsafe<None>
 */
function loadComponentsFromDirectory(string $directoryName):Unsafe {
    /** @var false|ViewEngineInterface */
    static $viewEngine = false;
    if (!$viewEngine) {
        $viewEngine = Container::get(ViewEngineInterface::class)->unwrap($error);
        if ($error) {
            return error($error);
        }
    }
    
    $viewEngine->loadComponentsFromDirectory($directoryName)->unwrap($loadError);
    if ($loadError) {
        return error($loadError);
    }
    return ok();
}

/**
 * Load a component from the contents of a file.
 * @param  string        $componentFullName Full name of the component.
 * @param  array<string> $componentAliases  A list of aliases for the component.
 * @param  string        $fileName          Path of the file.
 * @return Unsafe<None>
 */
function loadComponentFromFile(string $componentFullName, array $componentAliases, string $fileName):Unsafe {
    /** @var false|ViewEngineInterface */
    static $viewEngine = false;
    if (!$viewEngine) {
        $viewEngine = Container::get(ViewEngineInterface::class)->unwrap($error);
        if ($error) {
            return error($error);
        }
    }

    $viewEngine->loadComponentFromFile($componentFullName, $componentAliases, $fileName)->unwrap($loadError);
    if ($loadError) {
        return error($loadError);
    }
    return ok();
}

/**
 * Load a component from the contents of source code.
 * @param  string        $componentFullName Full name of the component.
 * @param  array<string> $componentAliases  A list of aliases for the component.
 * @param  string        $source            Source code.
 * @return Unsafe<None>
 */
function loadComponentFromSource(string $componentFullName, array $componentAliases, string $source):Unsafe {
    /** @var false|ViewEngineInterface */
    static $viewEngine = false;
    if (!$viewEngine) {
        $viewEngine = Container::get(ViewEngineInterface::class)->unwrap($error);
        if ($error) {
            return error($error);
        }
    }

    $viewEngine->loadComponentFromSource($componentFullName, $componentAliases, $source)->unwrap($loadError);
    if ($loadError) {
        return error($loadError);
    }
    return ok();
}