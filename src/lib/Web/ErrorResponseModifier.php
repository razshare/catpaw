<?php
namespace CatPaw\Web;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Response;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Core\XMLSerializer;
use CatPaw\Web\Interfaces\ResponseModifier;
use Throwable;

class ErrorResponseModifier implements ResponseModifier {
    /**
     *
     * @param  int                   $status
     * @param  string                $message
     * @param  array<string,string>  $headers
     * @return ErrorResponseModifier
     */
    public static function create(
        int $status,
        string $message,
        array $headers,
    ):self {
        return new self(
            status: $status,
            message: $message,
            headers: $headers,
        );
    }

    // private RequestContext $context;
    private mixed $body         = false;
    private string $contentType = TEXT_PLAIN;
    /** @var array<ResponseCookie> */
    private array $cookies = [];

    /**
     *
     * @param  int                  $status
     * @param  string               $message
     * @param  array<string,string> $headers
     * @return void
     */
    private function __construct(
        private int $status,
        private string $message,
        private array $headers,
    ) {
    }


    public function setCookies(ResponseCookie ...$cookies):void {
        $this->cookies = $cookies;
    }

    public function addCookies(ResponseCookie ...$cookies):void {
        $this->cookies = [...$this->cookies, ...$cookies];
    }

    public function setData(mixed $data):void {
    }

    public function setRequestContext(RequestContext $context):void {
        // $this->context = $context;
    }

    public function setHeaders(array $headers):void {
        $this->headers = $headers;
    }

    public function setStatus(int $status):void {
        $this->status = $status;
    }

    public function getData():mixed {
        return null;
    }

    public function getHeaders():array {
        return $this->headers;
    }

    public function getStatus():int {
        return $this->status;
    }

    public function as(string $contentType):self {
        $this->contentType = $contentType;
        return $this;
    }

    public function item():self {
        $this->body = ErrorItem::create(
            message: $this->message,
            status: $this->status,
        );
        return $this;
    }

    /**
     *
     * @return Unsafe<Response>
     */
    public function getResponse():Unsafe {
        if (APPLICATION_JSON === $this->contentType) {
            $body = json_encode($this->body);
            if (false === $body) {
                return error('Could not encode body to json.');
            }
        } else if (APPLICATION_XML === $this->contentType) {
            $body = is_object($this->body)
                    ?XMLSerializer::generateValidXmlFromObj($this->body)
                    :XMLSerializer::generateValidXmlFromArray($this->body);
        } else {
            $body = (string)$this->message;
        }

        $response = new Response(
            status: $this->status,
            headers: $this->headers,
            body: $body,
        );

        try {
            $response->setHeader('Content-Type', $this->contentType);
        } catch(Throwable $error) {
            return error($error);
        }

        foreach ($this->cookies as $cookie) {
            $response->setCookie($cookie);
        }

        return ok($response);
    }
}
