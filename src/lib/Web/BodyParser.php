<?php

namespace CatPaw\Web;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;

use CatPaw\Core\Unsafe;
use Exception;
use function json_decode;
use function mb_parse_str;

class BodyParser {
    private function __construct() {
    }

    /**
     * @return Unsafe<mixed>
     */
    public static function parse(
        string $body,
        string $contentType,
    ): Unsafe {
        if ('' === $contentType) {
            return error("No Content-Type specified. Could not parse body.");
        } 

        try {
            if (str_starts_with($contentType, "application/x-www-form-urlencoded")) {
                mb_parse_str($body, $result);
                return ok($result);
            } elseif (str_starts_with($contentType, "application/json")) {
                $result = json_decode($body, true);
                return ok($result);
            } elseif (str_starts_with($contentType, "multipart/")) {
                [,$result] = FormParser::parse($contentType, $body);
                return ok($result);
            } else {
                $result = [];
                return $result;
            }
        } catch (Exception) {
            $result = [];
            return $result;
        }
        return ok($body);
    }
}
