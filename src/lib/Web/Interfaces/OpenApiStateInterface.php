<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

interface OpenApiStateInterface {
    /**
     *
     * @param  string       $className
     * @return array<mixed>
     */
    public function templateForObjectComponent(string $className):array;

    /**
     *
     * @param  string       $className
     * @param  bool         $dataIsObject
     * @return array<mixed>
     */
    public function templateForItem(string $className, bool $dataIsObject = true):array;

    /**
     *
     * @param  string       $className
     * @param  bool         $dataIsObject
     * @return array<mixed>
     */
    public function templateForPage(string $className, bool $dataIsObject = true):array;
    
    /**
     * Get the current OpenAPI data.
     * You can safely expose this through a rest api.
     * @return array<mixed>
     */
    public function &data():array;

    public function withTitle(string $title):void;

    public function withVersion(string $title):void;

    /**
     *
     * @param  string       $path
     * @param  array<mixed> $pathContent
     * @return void
     */
    public function withPath(string $path, array $pathContent):void;

    public function withComponentReference(string $className):string;

    public function withComponentReferenceItem(string $className):string;

    public function withComponentReferencePage(string $className):string;

    /**
     *
     * @param  string       $className
     * @return Unsafe<None>
     */
    public function withComponentObject(string $className):Unsafe;


    /**
     * Create a deterministic ID for an operation.
     * Given the same inputs this function will always return the same ID.
     *
     * @param  string       $method     http method
     * @param  array<mixed> $parameters operation parameters
     * @param  array<mixed> $responses  operation responses
     * @return string
     */
    public function createOperationID(
        string $method,
        array $parameters,
        array $responses,
    ):string;

    /**
     *
     * @param  array<string> $tags
     * @param  string        $method
     * @param  string        $operationId
     * @param  string        $summary
     * @param  array<mixed>  $parameters
     * @param  array<mixed>  $requestBody
     * @param  array<mixed>  $responses
     * @return array<mixed>
     */
    public function createPathContent(
        array $tags,
        string $method,
        string $operationId,
        string $summary,
        array $parameters,
        array $requestBody,
        array $responses,
    ):array;

    /**
     *
     * @param  string                                    $name
     * @param  string                                    $in
     * @param  string                                    $description
     * @param  bool                                      $required
     * @param  array<mixed>                              $schema
     * @param  array<array<mixed>|string|int|float|bool> $examples
     * @return array<mixed>
     */
    public function createParameter(
        string $name,
        string $in,
        string $description,
        bool $required,
        array $schema,
        array $examples,
    ):array;

    /**
     *
     * @param  int                                $status
     * @param  string                             $description
     * @param  string                             $contentType
     * @param  array<mixed>                       $schema
     * @param  array<mixed>|string|int|float|bool $example
     * @return array<mixed>
     */
    public function createResponse(
        int $status,
        string $description,
        string $contentType,
        string|array $schema,
        mixed $example = []
    ):array;

    /**
     *
     * @param  string       $type
     * @param  array<mixed> $properties
     * @return array<mixed>
     */
    public function createSchema(
        string $type,
        array $properties = [],
    ):array;

    /**
     *
     * @param  string       $name
     * @param  string       $type
     * @param  string       $description
     * @return array<mixed>
     */
    public function createProperty(
        string $name,
        string $type,
        string $description,
    ):array;

    /**
     *
     * @param  string                             $title
     * @param  array<mixed>|string|int|float|bool $value
     * @param  string                             $summary
     * @return array<mixed>
     */
    public function createExample(
        string $title,
        array|string|int|float|bool $value,
        string $summary = '',
    ):array;

    /**
     *
     * @param  string       $description
     * @param  bool         $required
     * @param  array<mixed> $content
     * @return array<mixed>
     */
    public function createRequestBody(
        string $description,
        bool $required,
        array $content,
    ):array;

    /**
     *
     * @param  string                             $contentType
     * @param  array<mixed>                       $schema
     * @param  array<mixed>|string|int|float|bool $example
     * @return array<string,array<mixed>>
     */
    public function createRequestBodyContent(
        string $contentType,
        string|array $schema,
        mixed $example = []
    ): array;
}