<?php
namespace CatPaw\Web;

use CatPaw\Web\Interfaces\ViewInterface;

class View implements ViewInterface {
    public static function create():self {
        return new self();
    }

    private function __construct() {
    }

    public int $status = OK;
    /** @var array<string,string> */
    public array $headers = [];
    /** @var array<string,mixed> */
    public array $properties = [];

    public function withProperties(array $properties): self {
        $this->properties = $properties;
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