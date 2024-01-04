<?php

namespace CatPaw\Web;

use DateTime;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Stringable;

readonly class Cookie implements Stringable {
    /**
     * @param  RequestInterface $request
     * @return false|Cookie
     */
    public static function findFromRequestByName(RequestInterface $request, string $cookieName):false|Cookie {
        $cookies = self::listFromRequest($request);
        return $cookies[$cookieName] ?? false;
    }

    /**
     * @param  ResponseInterface $response
     * @return false|Cookie
     */
    public static function findFromResponseByName(ResponseInterface $response, string $cookieName):false|Cookie {
        $cookies = self::listFromResponse($response);
        return $cookies[$cookieName] ?? false;
    }

    /**
     * @param  ResponseInterface $response
     * @return false|Cookie
     */
    public static function findFromRequestContextByName(RequestContext $context, string $cookieName):false|Cookie {
        return Cookie::findFromResponseByName($context->response, $cookieName)
        ?:Cookie::findFromRequestByName($context->request, $cookieName)
        ?:false;
    }
    
    /**
     * @param  RequestInterface $request
     * @return array<Cookie>
     */
    public static function listFromRequest(RequestInterface $request):array {
        $cookies = [];
        $entries = $request->getHeader("Cookie");
        foreach ($entries as $raw) {
            $parts = explode('=', $raw, 2);
            $key   = $parts[0] ?? '';
            if (!$key) {
                continue;
            }
            $value         = $parts[1] ?? '';
            $cookies[$key] = new Cookie(urldecode($key), urldecode($value));
        }
        return $cookies;
    }


    /**
     * @param  ResponseInterface $response
     * @return array<Cookie>
     */
    public static function listFromResponse(ResponseInterface $response):array {
        $cookies = [];
        $entries = $response->getHeader("Cookie");
        foreach ($entries as $raw) {
            $parts = explode('=', $raw, 2);
            $key   = $parts[0] ?? '';
            if (!$key) {
                continue;
            }
            $value         = $parts[1] ?? '';
            $cookies[$key] = new Cookie(urldecode($key), urldecode($value));
        }
        return $cookies;
    }

    private false|DateTime $expiration;
    private bool $httpOnly;
    private bool $secure;

    public function __construct(
        public string $key,
        public string $value,
    ) {
        $this->expiration = false;
    }

    public function setExpiration(DateTime $expiration) {
        $this->expiration = $expiration;
    }

    public function setHttpOnly(bool $httpOnly) {
        $this->httpOnly = $httpOnly;
    }

    public function setSecure(bool $secure) {
        $this->secure = $secure;
    }

    public function addToResponse(ResponseInterface $response):void {
        $response->withHeader("Set-Cookie", (string)$this);
    }

    public function __toString(): string {
        $key    = urlencode($this->key);
        $value  = urlencode($this->value);
        $result = "$key=$value";

        if ($this->expiration) {
            $result .= "; Expires=".$this->expiration->format("D, j m Y H:i:s e");
        }

        if ($this->secure) {
            $result .= "; Secure";
        }

        if ($this->httpOnly) {
            $result .= "; HttpOnly";
        }

        return $result;
    }
}