<?php
namespace CatPaw\Web;

use Amp\Http\Server\Response;
use CatPaw\XMLSerializer;

class SuccessResponse {
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

    public Response $response;
    private function __construct(
        private mixed $data,
        private array $headers,
        private int $status,
        private string $message,
    ) {
        $this->response = new Response();
        $this->response->setStatus($status);
        $this->response->setHeaders($headers);
    }

    private function createStructuredPayload():array {
        return [
            "data"    => $this->data,
            "message" => $this->message,
            "status"  => $this->status,
        ];
    }

    public function forText():void {
        $this->response->setBody($this->data);
    }

    public function forJSON():void {
        $this->response->setBody(json_encode($this->createStructuredPayload()));
    }

    public function forXML():void {
        $this->response->setBody(XMLSerializer::generateValidXmlFromArray($this->createStructuredPayload()));
    }
}