<?php

namespace CatPaw\Web;

use Amp\Http\Server\Response;
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
    int $status = 200,
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
 * Something is wrong, notify the client with a code and a message.
 * @param  false|string          $message
 * @param  int                   $status
 * @param  array                 $headers
 * @return ErrorResponseModifier
 */
function failure(
    false|string $message = false,
    int $status = 500,
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