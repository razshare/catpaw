<?php
namespace CatPaw\Web;

use Amp\ByteStream\ReadableStream;
use Amp\Http\Server\Response;

use function CatPaw\error;
use function CatPaw\ok;
use CatPaw\Unsafe;
use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\XMLSerializer;
use Throwable;

class SuccessResponseModifier implements ResponseModifier {
    public static function create(
        mixed $data,
        array $headers,
        int $status,
        string $message,
    ):self {
        return new self(
            data: $data,
            headers: $headers,
            status: $status,
            message: $message,
        );
    }

    private const PRIMITIVE = 0;
    private const OBJECT    = 1;
    private const VIEW      = 2;
    private const STREAM    = 3;

    private int $type          = self::PRIMITIVE;
    private bool $isStructured = false;
    private false|Page $page   = false;

    private function __construct(
        private mixed $data,
        private array $headers,
        private int $status,
        private string $message,
    ) {
        if ($data instanceof ReadableStream) {
            $this->type = self::STREAM;
        } else if (is_object($this->data) || is_array($this->data)) {
            $this->type = self::OBJECT;
        } else {
            $this->type = self::PRIMITIVE;
        }
    }
    
    public function isPrimitive():bool {
        return self::PRIMITIVE === $this->type;
    }

    public function withStructure(bool $value = true): void {
        $this->isStructured = $value;
    }

    public function isPage():bool {
        return (bool)$this->page;
    }

    private function createStructuredPayload(string $wildcard):array {
        if ($this->page) {
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

            $data   = $shouldWrap?[$this->data]:$this->data;
            $result = [
                "type"                => "page",
                "previous{$wildcard}" => $this->page->previousLink(),
                "next{$wildcard}"     => $this->page->nextLink(),
                "previous"            => $this->page->previous(),
                "next"                => $this->page->next(),
                "data"                => $data,
                "message"             => $this->message,
                "status"              => $this->status,
            ];
            return $result;
        }

        return [
            "type"    => "item",
            "data"    => $this->data,
            "message" => $this->message,
            "status"  => $this->status,
        ];
    }

    public function forText(Response $response):Response {
        $payload = match ($this->type) {
            self::OBJECT => json_encode($this->data),
            self::STREAM => $this->data,
            default      => $this->data,
        };
        $response->setBody($payload);
        $response->setStatus($this->status);
        foreach ($this->headers as $key => $value) {
            $response->setHeader($key, $value);
        }
        return $response;
    }

    /**
     * @return Unsafe<Response>
     */
    public function forJson(Response $response):Unsafe {
        $payload = $this->isStructured?$this->createStructuredPayload(wildcard:'Href'):$this->data;
        try {
            $json = json_encode($payload);
        } catch(Throwable $e) {
            return error($e);
        }
        $response->setBody($json);
        $response->setStatus($this->status);
        foreach ($this->headers as $key => $value) {
            $response->setHeader($key, $value);
        }
        return ok($response);
    }

    public function forXml(Response $response):Response {
        $payload = $this->isStructured?$this->createStructuredPayload(wildcard:'Href'):$this->data;
        $response->setBody(XMLSerializer::generateValidXmlFromArray($payload));
        $response->setStatus($this->status);
        foreach ($this->headers as $key => $value) {
            $response->setHeader($key, $value);
        }
        return $response;
    }

    /**
     * Page the response.
     * @param  Page                    $page
     * @return SuccessResponseModifier
     */
    public function page(Page $page):self {
        $this->page = $page;
        return $this;
    }
}