<?php
namespace CatPaw\Web;

use Amp\Http\Server\Response;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Core\XMLSerializer;
use CatPaw\Web\Interfaces\ResponseModifier;
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
        private readonly int $status,
        private readonly string $message,
        private readonly array $headers,
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
            'type'    => 'error',
            'message' => $this->message,
            'status'  => $this->status,
        ];
    }

    public function forText(Response $response):Response {
        $response->setBody($this->message);
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
        $payload = $this->isStructured?$this->createStructuredPayload():$this->message;

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
        $payload = $this->isStructured?$this->createStructuredPayload():$this->message;
        $response->setBody(XMLSerializer::generateValidXmlFromArray($payload));
        $response->setStatus($this->status);
        foreach ($this->headers as $key => $value) {
            $response->setHeader($key, $value);
        }
        return $response;
    }

    /**
     * Page the response.
     * @param  Page                  $page
     * @return ErrorResponseModifier
     */
    public function page(Page $page):self {
        return $this;
    }
}