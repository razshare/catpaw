<?php
namespace CatPaw\Web;

use Amp\Http\Cookie\ResponseCookie;
use Amp\Http\Server\Response;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Core\XMLSerializer;
use CatPaw\Web\Interfaces\ResponseModifierInterface;
use Throwable;

/**
 * @template T
 * @package CatPaw\Web
 */
class SuccessResponseModifier implements ResponseModifierInterface {
    /**
     *
     * @param  T                          $data
     * @param  array<string,string>       $headers
     * @param  false|int                  $status
     * @param  string                     $message
     * @return SuccessResponseModifier<T>
     */
    public static function create(
        mixed $data,
        array $headers,
        false|int $status,
        string $message,
    ):self {
        return new self(
            data: $data,
            headers: $headers,
            status: $status,
            message: $message,
        );
    }

    private false|RequestContext $context = false;
    private false|Page $page              = false;
    private bool $bodyIsResponse          = false;
    private string $contentType           = TEXT_PLAIN;
    /** @var array<ResponseCookie> */
    private array $cookies = [];
    public mixed $body;

    /**
     *
     * @param T                    $data
     * @param array<string,string> $headers
     * @param false|int            $status
     * @param string               $message
     */
    private function __construct(
        private mixed $data,
        private array $headers,
        private false|int $status,
        private string $message,
    ) {
        $this->update();
    }

    /**
     * 
     * @param  ResponseCookie             ...$cookies
     * @return SuccessResponseModifier<T>
     */
    public function withCookies(ResponseCookie ...$cookies):self {
        $this->cookies = $cookies;
        return $this;
    }

    /**
     * 
     * @param  ResponseCookie             ...$cookies
     * @return SuccessResponseModifier<T>
     */
    public function addCookies(ResponseCookie ...$cookies):self {
        $this->cookies = [...$this->cookies, ...$cookies];
        return $this;
    }

    /**
     * 
     * @param  mixed                      $data
     * @return SuccessResponseModifier<T>
     */
    public function withData(mixed $data):self {
        $this->data = $data;
        $this->update();
        return $this;
    }
    
    /**
     * 
     * @param  RequestContext             $context
     * @return SuccessResponseModifier<T>
     */
    public function withRequestContext(RequestContext $context):self {
        $this->context = $context;
        return $this;
    }

    /**
     * 
     * @param  array<string, string>      $headers
     * @return SuccessResponseModifier<T>
     */
    public function withHeaders(array $headers):self {
        $this->headers = $headers;
        $this->update();
        return $this;
    }

    /**
     * 
     * @param  int                        $status
     * @return SuccessResponseModifier<T>
     */
    public function withStatus(int $status):self {
        $this->status = $status;
        $this->update();
        return $this;
    }

    public function data():mixed {
        return $this->data;
    }

    /**
     *
     * @return array<string,string>
     */
    public function headers():array {
        return $this->headers;
    }

    public function status():int {
        return $this->status;
    }


    private function update():void {
        if ($this->data instanceof Response) {
            $this->bodyIsResponse = true;
            foreach ($this->headers as $key => $value) {
                $this->data->setHeader($key, $value);
            }
            if (false !== $this->status) {
                $this->data->setStatus($this->status);
            }
        } else if (false === $this->status) {
            $this->status = 200;
        }

        if (!$this->message) {
            $this->message = HttpStatus::reason($this->status);
        }

        $this->body = $this->data;
    }

    /**
     *
     * @param  string                     $contentType
     * @return SuccessResponseModifier<T>
     */
    public function as(string $contentType):self {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * @return SuccessResponseModifier<T>
     */
    public function item():self {
        $this->body = SuccessItem::create(
            data: $this->data,
            message: $this->message,
            status: $this->status,
        );
        return $this;
    }

    /**
     * Page the response.
     * @param  Page                       $page
     * @return SuccessResponseModifier<T>
     */
    public function page(Page $page):self {
        $this->page = $page;
        if (is_array($this->data)) {
            $count = count($this->data);
            if ($count > 0 && !isset($this->data[0])) {
                $shouldWrap = true;
            } else {
                $shouldWrap = false;
            }
        } else {
            $shouldWrap = true;
        }

        $data       = $shouldWrap?[$this->data]:$this->data;
        $this->body = SuccessPage::create(
            data: $data,
            message: $this->message,
            status: $this->status,
            previousHref: $this->page->previousLink(),
            nextHref: $this->page->nextLink(),
            previousPage: $this->page->previous(),
            nextPage: $this->page->next(),
        );

        return $this;
    }

    /**
     *
     * @return Result<Response>
     */
    public function response():Result {
        if ($this->bodyIsResponse) {
            return ok($this->body);
        }

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
            if (is_array($this->body) || is_object($this->body)) {
                $body = serialize($this->body);
                $body = serialize($this->body);
            } else {
                $body = (string)$this->body;
            }
        }

        $response = new Response(
            status: $this->status,
            headers: $this->headers,
            body: $body,
        );

        if ($this->context) {
            foreach ($this->context->cookies as $cookie) {
                if ($response->getCookie($cookie->getName())) {
                    continue;
                }
                $response->setCookie($cookie);
            }
        }

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
