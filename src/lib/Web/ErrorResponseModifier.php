<?php
namespace CatPaw\Web;

use function CatPaw\error;
use function CatPaw\ok;
use CatPaw\Unsafe;
use CatPaw\Web\Interfaces\ResponseModifier;

use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response;
use Throwable;

class ErrorResponseModifier implements ResponseModifier {
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

    private bool $isStructured = true;
    
    private function __construct(
        private int $status,
        private string $message,
        private array $headers,
    ) {
    }

    public function isPrimitive():bool {
        return true;
    }

    public function isPage():bool {
        return false;
    }

    public function withStructure(bool $value = true): void {
        $this->isStructured = $value;
    }

    private function createStructuredPayload():array {
        return [
            "type"    => 'error',
            "message" => $this->message,
            "status"  => $this->status,
        ];
    }

    public function forText(ResponseInterface $response):ResponseInterface {
        $plaintext = Response::plaintext($this->message);
        $plaintext->withStatus($this->status);
        foreach ($response->getHeaders() as $key => $value) {
            $plaintext->withHeader($key, $value);
        }
        foreach ($this->headers as $key => $value) {
            $plaintext->withHeader($key, $value);
        }
        return $plaintext;
    }

    /**
     * @return Unsafe<ResponseInterface>
     */
    public function forJson(ResponseInterface $response):Unsafe {
        $payload = $this->isStructured?$this->createStructuredPayload():$this->message;

        try {
            $json = Response::json($payload);
        } catch(Throwable $e) {
            return error($e);
        }

        $json->withStatus($this->status);
        foreach ($response->getHeaders() as $key => $value) {
            $json->withHeader($key, $value);
        }
        foreach ($this->headers as $key => $value) {
            $json->withHeader($key, $value);
        }
        return ok($json);
    }

    public function forXml(ResponseInterface $response):ResponseInterface {
        $payload = $this->isStructured?$this->createStructuredPayload():$this->message;
        $xml     = Response::xml($payload);
        $xml->withStatus($this->status);
        foreach ($response->getHeaders() as $key => $value) {
            $xml->withHeader($key, $value);
        }
        foreach ($this->headers as $key => $value) {
            $xml->withHeader($key, $value);
        }
        return $xml;
    }

    /**
     * Page the response.
     * @param  Page                    $page
     * @return SuccessResponseModifier
     */
    public function page(Page $page):self {
        return $this;
    }
}