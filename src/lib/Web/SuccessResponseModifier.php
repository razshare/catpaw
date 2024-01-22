<?php
namespace CatPaw\Web;

use Amp\Http\Server\Response;

use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Core\XMLSerializer;
use CatPaw\Web\Interfaces\ResponseModifier;
use Throwable;

/**
 * @template T
 * @package CatPaw\Web
 */
class SuccessResponseModifier implements ResponseModifier {
    /**
     *
     * @param  T                       $data
     * @param  array                   $headers
     * @param  false|int               $status
     * @param  string                  $message
     * @return SuccessResponseModifier
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

    private RequestContext $context;
    private false|Page $page = false;
    public mixed $body;
    private bool $bodyIsResponse = false;
    private string $contentType  = __TEXT_PLAIN;

    /**
     *
     * @param  T         $data
     * @param  array     $headers
     * @param  false|int $status
     * @param  string    $message
     * @return void
     */
    private function __construct(
        private mixed $data,
        private array $headers,
        private false|int $status,
        private string $message,
    ) {
        $this->update();
    }

    public function setData(mixed $data) {
        $this->data = $data;
        $this->update();
    }

    public function setRequestContext(RequestContext $context) {
        $this->context = $context;
    }

    public function setHeaders(array $headers) {
        $this->headers = $headers;
        $this->update();
    }

    public function setStatus(int $status) {
        $this->status = $status;
        $this->update();
    }

    public function getData():mixed {
        return $this->data;
    }

    public function getHeaders():array {
        return $this->headers;
    }

    public function getStatus():int {
        return $this->status;
    }


    private function update() {
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
            $this->message = HttpStatus::getReason($this->status);
        }

        $this->body = $this->data;
    }

    public function as(string $contentType):self {
        $this->contentType = $contentType;
        return $this;
    }

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
     * @param  Page                    $page
     * @param  string                  $contentType
     * @return SuccessResponseModifier
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
     * @return Unsafe<Response>
     */
    public function getResponse():Unsafe {
        if ($this->bodyIsResponse) {
            return ok($this->body);
        }

        if (__APPLICATION_JSON === $this->contentType) {
            $body = json_encode($this->body);
            if (false === $body) {
                return error('Could not encode body to json.');
            }
        } else if (__APPLICATION_XML === $this->contentType) {
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

        return ok($response);
    }
}
