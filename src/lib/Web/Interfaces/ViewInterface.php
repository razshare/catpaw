<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Web\Body;

interface ViewInterface {
    /**
     * Get the http status of the view.
     * @return int
     */
    public function getStatus():int;

    /**
     * Get all http headers of the view.
     * @return array<string,mixed>
     */
    public function getHeaders():array;

    /**
     * Get all properties of the view.
     * @return array<string,mixed>
     */
    public function getProperties():array;

    /**
     * Set the http status.
     * @param  int           $status
     * @return ViewInterface
     */
    public function withStatus(int $status):self;

    /**
     * Set all headers.
     * @param  array<string,mixed> $headers
     * @return ViewInterface
     */
    public function withHeaders(array $headers):self;

    /**
     * Set a header.
     * @param  string        $key
     * @param  mixed         $value
     * @return ViewInterface
     */
    public function withHeader(string $key, mixed $value):self;

    /**
     * Unset a header.
     * @param  string        $key
     * @return ViewInterface
     */
    public function withoutHeader(string $key):self;

    /**
     * Set all properties.
     * @param  Body|object|array<string,mixed> $properties
     * @return ViewInterface
     */
    public function withProperties(object|array $properties):self;

    /**
     * Set a property.
     * @param  string        $key
     * @param  mixed         $value
     * @return ViewInterface
     */
    public function withProperty(string $key, mixed $value):self;

    /**
     * Unset a property.
     * @param  string        $key
     * @return ViewInterface
     */
    public function withoutProperty(string $key):self;
}