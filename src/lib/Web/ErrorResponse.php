<?php
namespace CatPaw\Web;

use CatPaw\XMLSerializer;
use React\Http\Message\Response;

class ErrorResponse {
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

    public Response $response;
    private function __construct(
        private int $status,
        private string $message,
        private array $headers,
    ) {
        $this->response = new Response();
        $this->response->withStatus($status);
        foreach ($headers as $key => $value) {
            $this->response->withHeader($key, $value);
        }
    }

    private function createStructuredPayload():array {
        return [
            "data"    => '',
            "message" => $this->message,
            "status"  => $this->status,
        ];
    }

    public function forText():void {
        $this->response->withBody(Stream::fromString($this->message));
    }

    public function forJson():void {
        $this->response->withBody(Stream::fromString(json_encode($this->createStructuredPayload())));
    }

    public function forXml():void {
        $this->response->withBody(Stream::fromString(XMLSerializer::generateValidXmlFromArray($this->createStructuredPayload())));
    }
}