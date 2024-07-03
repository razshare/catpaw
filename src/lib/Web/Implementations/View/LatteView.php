<?php
namespace CatPaw\Web\Implementations\View;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Web\Interfaces\ViewInterface;

#[Provider]
class LatteView implements ViewInterface {
    public int $status = 200;

    /** @var array<string,mixed> */
    public array $headers = [];

    /** @var array<string,mixed> */
    public array $properties = [];

    public function status(): int {
        return $this->status;
    }

    /**
     * @return array<string,mixed>
     */
    public function headers(): array {
        return $this->headers;
    }

    public function properties(): array {
        return $this->properties;
    }

    public function withStatus(int $status): ViewInterface {
        $this->status = $status;
        return $this;
    }

    public function withHeaders(array $headers): self {
        $this->headers = $headers;
        return $this;
    }

    public function withHeader(string $key, mixed $value):self {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withoutHeader(string $key):self {
        unset($this->headers[$key]);
        return $this;
    }

    public function withProperties(object|array $properties): self {
        if (is_object($properties)) {
            $this->properties = (array)$properties;
        } else {
            $this->properties = $properties;
        }
        return $this;
    }

    public function withProperty(string $key, mixed $value):self {
        $this->properties[$key] = $value;
        return $this;
    }

    public function withoutProperty(string $key):self {
        unset($this->properties[$key]);
        return $this;
    }
}