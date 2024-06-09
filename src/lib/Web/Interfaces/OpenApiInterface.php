<?php
namespace CatPaw\Web\Interfaces;

interface OpenApiInterface {
    /**
     * Get the current OpenAPI data.
     * You can safely expose this through a rest api.
     * @return array<mixed>
     */
    public function getData():array;

    public function setTitle(string $title):void;

    public function setVersion(string $title):void;
}