<?php
namespace CatPaw\Web\Interfaces;

interface OpenApiInterface {
    /**
     * Get the current OpenAPI data.
     * You can safely expose this through a rest api.
     * @return array<mixed>
     */
    public function data():array;

    public function withTitle(string $title):void;

    public function withVersion(string $title):void;
}