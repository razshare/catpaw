<?php

namespace CatPaw\Web\Implementations\OpenApiState;

use CatPaw\Core\Attributes\ArrayList;
use CatPaw\Core\Attributes\HashMap;
use CatPaw\Core\Attributes\Provider;

use function CatPaw\Core\error;

use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Interfaces\OpenApiStateInterface;
use ReflectionClass;
use Throwable;

#[Provider]
class SimpleOpenApiState implements OpenApiStateInterface {
    /**
     *
     * @param  string       $className
     * @return array<mixed>
     */
    public function templateForObjectComponent(string $className):array {
        return [
            'type' => 'object',
            '$ref' => "#/components/schemas/{$className}",
        ];
    }

    /**
     *
     * @param  string       $className
     * @param  bool         $dataIsObject
     * @return array<mixed>
     */
    public function templateForItem(string $className, bool $dataIsObject = true):array {
        if ($dataIsObject) {
            $data = [
                'type' => 'object',
                '$ref' => "#/components/schemas/{$className}",
            ];
        } else {
            $data = [
                'type' => $className,
            ];
        }
        return [
            'type'       => 'object',
            'properties' => [
                "type" => [
                    "type" => "string",
                ],
                "status" => [
                    "type"   => "integer",
                    "format" => "int32",
                ],
                "message" => [
                    "type" => "string",
                ],
                "data" => $data,
            ],
        ];
    }

    /**
     *
     * @param  string       $className
     * @param  bool         $dataIsObject
     * @return array<mixed>
     */
    public function templateForPage(string $className, bool $dataIsObject = true):array {
        if ($dataIsObject) {
            $data = [
                'type'  => 'array',
                'items' => [
                    '$ref' => "#/components/schemas/{$className}",
                ],
            ];
        } else {
            $data = [
                'type'  => 'array',
                'items' => [
                    'type' => $className,
                ],
            ];
        }
        return [
            'type'       => 'object',
            'properties' => [
                "type" => [
                    "type" => "string",
                ],
                "status" => [
                    "type"   => "integer",
                    "format" => "int32",
                ],
                "message" => [
                    "type" => "string",
                ],
                "previousHref" => [
                    "type" => "string",
                ],
                "nextHref" => [
                    "type" => "string",
                ],
                "previous" => [
                    "type"       => "object",
                    "properties" => [
                        "start" => [
                            "type"   => "integer",
                            "format" => "int32",
                        ],
                        "size" => [
                            "type"   => "integer",
                            "format" => "int32",
                        ],
                    ],
                ],
                "next" => [
                    "type"       => "object",
                    "properties" => [
                        "start" => [
                            "type"   => "integer",
                            "format" => "int32",
                        ],
                        "size" => [
                            "type"   => "integer",
                            "format" => "int32",
                        ],
                    ],
                ],
                "data" => $data,
            ],
        ];
    }

    /** @var array<mixed> */
    public array $json = [
        'openapi' => '3.0.3',
        'info'    => [
            'title'   => 'OpenAPI',
            'version' => '0.0.1',
        ],
        'paths'      => [],
        'components' => [
            'schemas' => [],
        ],
    ];

    /**
     * Get the current OpenAPI data.
     * You can safely expose this through a rest api.
     * @return array<mixed>
     */
    public function &data():array {
        return $this->json;
    }

    public function withTitle(string $title):void {
        $this->json['info']['title'] = $title;
    }

    public function withVersion(string $title):void {
        $this->json['info']['version'] = $title;
    }

    /**
     *
     * @param  string       $path
     * @param  array<mixed> $pathContent
     * @return void
     */
    public function withPath(string $path, array $pathContent):void {
        if (isset($this->json['paths'][$path])) {
            $this->json['paths'][$path] = [
                ...$this->json['paths'][$path],
                ...$pathContent,
            ];
            return;
        }
        $this->json['paths'][$path] = $pathContent;
    }

    public function withComponentReference(string $className):string {
        $this->json['components']['schemas'][$className] = self::templateForObjectComponent($className);
        return "#/components/schemas/{$className}";
    }

    public function withComponentReferenceItem(string $className):string {
        $this->json['components']['schemas']["{$className}Item"] = self::templateForItem($className);
        return "#/components/schemas/{$className}Item";
    }

    public function withComponentReferencePage(string $className):string {
        $this->json['components']['schemas']["{$className}Page"] = self::templateForPage($className);
        return "#/components/schemas/{$className}Page";
    }

    /**
     *
     * @param  string       $className
     * @return Unsafe<None>
     */
    public function withComponentObject(string $className):Unsafe {
        try {
            $resolvedProperties = [];
            $reflection         = new ReflectionClass($className);

            foreach ($reflection->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();

                $reflectionNamedType = ReflectionTypeManager::unwrap($reflectionProperty);
                $type                = $reflectionNamedType?$reflectionNamedType->getName():'string';

                if (class_exists($type)) {
                    $this->withComponentObject($type)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $resolvedProperties[$propertyName] = [
                        'type' => 'object',
                        '$ref' => "#/components/schemas/{$type}",
                    ];
                } else {
                    /** @var false|ArrayList $arrayListAttribute */
                    $arrayListAttribute = ArrayList::findByProperty($reflectionProperty)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }

                    $hashMapAttribute = HashMap::findByProperty($reflectionProperty)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }


                    if ($arrayListAttribute || $hashMapAttribute) {
                        if ($arrayListAttribute) {
                            $subType = $arrayListAttribute->className;
                        } else {
                            $subType = $hashMapAttribute->className;
                        }

                        if (class_exists($subType)) {
                            $this->withComponentObject($subType)->unwrap($error);
                            if ($error) {
                                return error($error);
                            }
                            if ($arrayListAttribute) {
                                $resolvedProperties[$propertyName] = [
                                    'type'  => 'array',
                                    'items' => [
                                        '$ref' => "#/components/schemas/{$subType}",
                                    ],
                                ];
                            } else {
                                $resolvedProperties[$propertyName] = [
                                    'type'  => 'array',
                                    'items' => [
                                        'key'  => [ 'type' => 'string' ],
                                        '$ref' => "#/components/schemas/{$subType}",
                                    ],
                                ];
                            }
                        } else {
                            if ($arrayListAttribute) {
                                $resolvedProperties[$propertyName] = [
                                    'type'  => 'array',
                                    'items' => [
                                        'type' => match ($type) {
                                            'int'   => 'integer',
                                            'float' => 'number',
                                            'bool'  => 'boolean',
                                            default => $type,
                                        },
                                    ],
                                ];

                                if ('integer' === $resolvedProperties[$propertyName]['items']['type']) {
                                    $resolvedProperties[$propertyName]['items']['format'] = 'int32';
                                }
                            } else {
                                $resolvedProperties[$propertyName] = [
                                    'type'  => 'array',
                                    'items' => [
                                        'key'  => [ 'type' => 'string' ],
                                        'type' => match ($type) {
                                            'int'   => 'integer',
                                            'float' => 'number',
                                            'bool'  => 'boolean',
                                            default => $type,
                                        },
                                    ],
                                ];

                                if ('integer' === $resolvedProperties[$propertyName]['items']['type']) {
                                    $resolvedProperties[$propertyName]['items']['format'] = 'int32';
                                }
                            }
                        }
                        continue;
                    }

                    $resolvedProperties[$propertyName] = [
                        'type' => match ($type) {
                            'int'   => 'integer',
                            'float' => 'number',
                            'bool'  => 'boolean',
                            default => $type,
                        },
                    ];

                    if ('integer' === $resolvedProperties[$propertyName]['type']) {
                        $resolvedProperties[$propertyName]['format'] = 'int32';
                    }
                }
            }
            $this->json['components']['schemas'][$className] = [
                'type'       => 'object',
                'properties' => (object)$resolvedProperties,
            ];
            return ok();
        } catch(Throwable $e) {
            return error($e);
        }
    }


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
    ):string {
        $parametersIDs = [];
        $responsesKeys = \join('-', \array_keys($responses));
        foreach ($parameters as $parameter) {
            $name            = $parameter['name'];
            $in              = $parameter['in'];
            $parametersIDs[] = "n.$name;i.$in";
        }
        $parametersIDs = \join(';', $parametersIDs);
        return \sha1("$method:$parametersIDs:$responsesKeys");
    }

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
    ):array {
        $method = \strtolower($method);

        $result = [
            "$method" => [
                "tags"        => $tags,
                "summary"     => $summary,
                "operationId" => $operationId,
                "parameters"  => $parameters,
                "requestBody" => $requestBody,
                "responses"   => $responses,
            ],
        ];

        if (!$result["$method"]['parameters']) {
            unset($result["$method"]['parameters']);
        }
        if (!$result["$method"]['requestBody']) {
            unset($result["$method"]['requestBody']);
        }
        if (!$result["$method"]['responses']) {
            unset($result["$method"]['responses']);
        }

        return $result;
    }

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
    ):array {
        return [[
            "name"        => $name,
            "in"          => $in,
            "description" => $description,
            "required"    => $required,
            "schema"      => $schema,
            "examples"    => $examples,
        ]];
    }

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
    ):array {
        $response = [
            "$status" => [
                "description" => $description,
                "content"     => [
                    "$contentType" => [
                        "schema"  => $schema,
                        "example" => $example,
                    ],
                ],
            ],
        ];

        return $response;
    }

    /**
     *
     * @param  string       $type
     * @param  array<mixed> $properties
     * @return array<mixed>
     */
    public function createSchema(
        string $type,
        array $properties = [],
    ):array {
        $schema = [
            "type"       => $type,
            "properties" => $properties,
        ];

        if (\count($properties) === 0) {
            unset($schema["properties"]);
        }

        return $schema;
    }

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
    ):array {
        return [
            "$name" => [
                "type"        => $type,
                "description" => $description,
            ],
        ];
    }

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
    ):array {
        $example = [
            "$title" => [
                "summary" => $summary,
                "value"   => $value,
            ],
        ];

        if ('' === $summary) {
            unset($example[$title]["summary"]);
        }

        return $example;
    }

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
    ):array {
        if (!$content) {
            return [];
        }
        return [
            "description" => $description,
            "required"    => $required,
            "content"     => $content,
        ];
    }

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
    ): array {
        return [
            "$contentType" => [
                "schema"  => $schema,
                "example" => $example,
            ],
        ];
    }
}
