<?php
namespace CatPaw\Web\Interfaces;

interface ViewInterface {
    /**
     * Set all properties.
     * @param  array<string,mixed> $properties
     * @return ViewInterface
     */
    public function withProperties(array $properties):self;

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