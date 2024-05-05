<?php
namespace CatPaw\Web\Interfaces;

interface RenderContextInterface {
    /**
     *
     * @param  array<mixed> $properties
     * @return self
     */
    public function setProperties(array $properties):self;

    public function setProperty(string $key, mixed $value):self;

    public function unsetProperty(string $key):self;

    /**
     *
     * @param  int                  $status
     * @param  array<string,string> $headers
     * @return ResponseModifier
     */
    public function render(int $status = 200, array $headers = []):ResponseModifier;
}