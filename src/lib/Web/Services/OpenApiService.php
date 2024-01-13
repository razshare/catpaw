<?php

namespace CatPaw\Web\Services;

use CatPaw\Core\Attributes\ArrayList;
use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;

use ReflectionClass;
use Throwable;

#[Service]
class OpenApiService {
    public static function templateForItem(string $className, bool $dataIsObject = true):array {
        if ($dataIsObject) {
            $data = [
                'type' => 'array',
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
                    "type" => "integer",
                ],
                "message" => [
                    "type" => "string",
                ],
                "data" => $data,
            ],
        ];
    }

    public static function templateForPage(string $className, bool $dataIsObject = true):array {
        if ($dataIsObject) {
            $data = [
                'type' => 'array',
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
                    "type" => "integer",
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
                            "type" => "integer",
                        ],
                        "size" => [
                            "type" => "integer",
                        ],
                    ],
                ],
                "next" => [
                    "type"       => "object",
                    "properties" => [
                        "start" => [
                            "type" => "integer",
                        ],
                        "size" => [
                            "type" => "integer",
                        ],
                    ],
                ],
                "data" => $data,
            ],
        ];
    }

    private array $json = [
        'openapi' => '3.0.0',
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
     * @return array
     */
    public function getData():array {
        return $this->json;
    }

    public function setTitle(string $title):void {
        $this->json['info']['title'] = $title;
    }
    
    public function setVersion(string $title):void {
        $this->json['info']['version'] = $title;
    }

    public function setPath(string $path, array $pathContent):void {
        if (isset($this->json['paths'][$path])) {
            $this->json['paths'][$path] = [
                ...$this->json['paths'][$path],
                ...$pathContent,
            ];
            return;
        }
        $this->json['paths'][$path] = $pathContent;
    }


    public function setComponentReferenceItem(string $className):void {
        $this->json['components']['schemas']["{$className}Item"] = self::templateForItem($className);
    }

    public function setComponentReferencePage(string $className):void {
        $this->json['components']['schemas']["{$className}Page"] = self::templateForPage($className);
    }

    /**
     * 
     * @param  string       $className
     * @return Unsafe<void>
     */
    public function setComponentObject(string $className):Unsafe {
        try {
            $resolvedProperties = [];
            $reflection         = new ReflectionClass($className);
            
            foreach ($reflection->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();

                $type = \CatPaw\Core\ReflectionTypeManager::unwrap($reflectionProperty)?->getName() ?? 'string';

                if (class_exists($type)) {
                    $this->setComponentObject($type)->try($error);
                    if ($error) {
                        return error($error);
                    }
                    $resolvedProperties[$propertyName] = [
                        'type' => 'object',
                        '$ref' => "#/components/schemas/{$type}",
                    ];
                } else {
                    /** @var false|ArrayList $arrayListAttribute */
                    $arrayListAttribute = ArrayList::findByProperty($reflectionProperty)->try($error);
                    if ($error) {
                        return error($error);
                    }

                    if ($arrayListAttribute) {
                        $subType = $arrayListAttribute->className;
                        $this->setComponentObject($subType)->try($error);
                        if ($error) {
                            return error($error);
                        }
                        $resolvedProperties[$propertyName] = [
                            'type'  => 'array',
                            'items' => [
                                '$ref' => "#/components/schemas/{$subType}",
                            ],
                        ];
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
     * @param  string $method     http method
     * @param  array  $parameters operation parameters
     * @param  array  $responses  operation responses
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
     * @param string $method
     * @param string $operationID
     * @param string $summary
     * @param array  $parameters
     * @param array  $requestBody
     * @param array  $responses
     * @return array<string,array{
     *      summary: string,
     *      operationId: string,
     *      parameters: array,
     *      requestBody: array,
     *      responses: array,
     * }>
     */
    public function createPathContent(
        string $method,
        string $operationID,
        string $summary,
        array $parameters,
        array $requestBody,
        array $responses,
    ):array {
        $method = \strtolower($method);

        $result = [
            "$method" => [
                "summary"     => $summary,
                "operationId" => $operationID,
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
     * @param string                             $name
     * @param string                             $in
     * @param string                             $description
     * @param bool                               $required
     * @param array                              $schema
     * @param array<array|string|int|float|bool> $examples
     * @return array<int,array{
     *      name: string,
     *      in: string,
     *      description: string,
     *      required: bool,
     *      schema: array,
     *      examples: array<array|string|int|float|bool>,
     * }>
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
     * @param int                         $status
     * @param string                      $description
     * @param string                      $contentType
     * @param array                       $schema
     * @param array|string|int|float|bool $example
     * @return array<string, array{
     *      content: array<string, array{
     *          example: array<array-key, mixed>|scalar, schema: array<array-key, mixed>
     *      }>, 
     *      description: string
     * }>
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

        // if (is_array($example) && \count($example) === 0) {
        //     unset($response[(string)$status]["content"][$contentType]["example"]);
        // }

        return $response;
    }

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
     * @param string $description
     * @param bool   $required
     * @param array  $content
     * @return array{}|array{
     *  description: string,
     *  required: bool,
     *  content: array<array-key,mixed>,
     * }
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
     * @param string                      $contentType
     * @param array                       $schema
     * @param array|string|int|float|bool $example
     * @return array<string,array{
     *      schema: array,
     *      example: array|string|int|float|bool
     * }>
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