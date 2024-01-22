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

    private false|Page $page     = false;
    private mixed $body          = false;
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
        if ($data instanceof Response) {
            $this->bodyIsResponse = true;
            foreach ($headers as $key => $value) {
                $data->setHeader($key, $value);
            }
            if (false !== $status) {
                $data->setStatus($status);
            }
        } else if (false === $status) {
            $this->status = 200;
        }

        if (!$message) {
            $this->message = HttpStatus::getReason($this->status);
        }

        $this->body = $data;
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

        try {
            $response->setHeader('Content-Type', $this->contentType);
        } catch(Throwable $error) {
            return error($error);
        }

        return ok($response);
    }
}
