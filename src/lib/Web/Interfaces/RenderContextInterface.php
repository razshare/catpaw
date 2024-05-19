<?php
namespace CatPaw\Web\Interfaces;

interface RenderContextInterface {
    /**
     * Set all properties.
     * @param  array<mixed> $properties
     * @return self
     */
    public function withProperties(array $properties):self;

    /**
     * Set a property.
     * @param  string                 $key
     * @param  mixed                  $value
     * @return RenderContextInterface
     */
    public function withProperty(string $key, mixed $value):self;

    /**
     * Unset a property.
     * @param  string                 $key
     * @return RenderContextInterface
     */
    public function withoutProperty(string $key):self;

    /**
     * Create a response modifier.
     * @param  int                  $status
     * @param  array<string,string> $headers
     * @return ResponseModifier
     */
    public function response(int $status = 200, array $headers = []):ResponseModifier;
}