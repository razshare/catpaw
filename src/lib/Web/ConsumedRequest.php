<?php
namespace CatPaw\Web;

use CatPaw\Attributes\Entry;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\ReflectionTypeManager;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Web\Services\OpenApiService;
use ReflectionClass;

class ConsumedRequest implements AttributeInterface {
    use CoreAttributeDefinition;
    use SchemaEncoder;

    public static function create(
        array|string $className = '',
        string $type = 'text/plain',
        string $description = '',
        mixed $example = [],
    ):self {
        return new self(
            className: $className,
            type: $type,
            description: $description,
            example: $example,
        );
    }

    private array $request = [];

    /**
     * 
     * @param  string $className   a class name or an array 1 one single element, which must be a class name.
     * @param  string $type        http content-type, when passed to #[Consumes], this content-type will be enforced
     * @param  string $description
     * @param  mixed  $example     an example of the body of the response
     * @return void
     */
    private function __construct(
        private string $className = '',
        private string $type = 'text/plain',
        private string $description = '',
        private mixed $example = [],
    ) {
    }
    
    /**
     * @param  string       $schema
     * @return string|array
     */
    private static function adaptClassToSchema(string $schema):string|array {
        if (class_exists($schema)) {
            $composed   = [];
            $reflection = new ReflectionClass($schema);
            
            foreach ($reflection->getProperties() as $property) {
                $name = $property->getName();
                if (!$type = ReflectionTypeManager::unwrap($property)) {
                    continue;
                }

                $classname = $type->getName();

                if ("array" === $classname) {
                    foreach ($property->getAttributes() as $attribute) {
                        $arguments = $attribute->getArguments();
                        if ((count($arguments)) >= 1) {
                            $classname = $arguments[0];
                            break;
                        }
                    }
                    if (is_array($classname)) {
                        $composed[$name] = [];
                        foreach ($classname as $key => $value) {
                            $composed[$name][$key] = self::adaptClassToSchema($value);
                        }
                    } else {
                        $composed[$name] = [self::adaptClassToSchema($classname)];
                    }
                } else {
                    $composed[$name] = self::adaptClassToSchema($classname);
                }
            }
            return $composed;
        }
        return $schema;
    }

    public function getContentType():string {
        return $this->type;
    }

    public function getValue():array {
        return $this->request;
    }

    public function getClassName():string {
        return $this->className;
    }

    #[Entry] public function setup(OpenApiService $oa):void {
        $isClass = class_exists($this->className);
        if ($isClass) {
            $oa->setComponentObject($this->className);
        }

        if ($isClass) {
            $schema = [
                'type' => 'object',
                '$ref' => "#/components/schemas/{$this->className}",
            ];
        } else {
            if ('array' === $this->className) {
                $schema = [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string'
                    ],
                ];
            } else {
                $schema = [
                    'type' => match ($this->className) {
                        'int'   => 'integer',
                        'float' => 'number',
                        'bool'  => 'boolean',
                        default => $this->className,
                    },
                ];
            }
        }

        $this->request = $oa->createRequestBodyContent(
            contentType: $this->type,
            schema: $schema,
            example: $this->example,
        );
    }
}