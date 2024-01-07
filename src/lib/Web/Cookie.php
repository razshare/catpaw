<?php

namespace CatPaw\Web;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Stringable;

readonly class Cookie implements Stringable {
    /**
     * @param  Request      $request
     * @return false|Cookie
     */
    public static function findFromRequestByName(Request $request, string $cookieName):false|Cookie {
        $cookies = self::listFromRequest($request);
        return $cookies[$cookieName] ?? false;
    }

    /**
     * @param  Response     $response
     * @return false|Cookie
     */
    public static function findFromResponseByName(Response $response, string $cookieName):false|Cookie {
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
     * @param  Request       $request
     * @return array<Cookie>
     */
    public static function listFromRequest(Request $request):array {
        return $request->getCookies();
    }


    /**
     * @param  Response      $response
     * @return array<Cookie>
     */
    public static function listFromResponse(Response $response):array {
        return $response->getCookies();
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