<?php
namespace CatPaw\Web;

use CatPaw\Attributes\Entry;
use function CatPaw\error;
use CatPaw\Interfaces\AttributeInterface;

use function CatPaw\ok;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Unsafe;
use CatPaw\Web\Services\OpenApiService;

class ProducedResponse implements AttributeInterface {
    use CoreAttributeDefinition;
    use SchemaEncoder;

    public static function create(
        string $type = 'text/plain',
        int $status = 200,
        string $className = '',
        string $description = '',
        mixed $example = [],
        bool $isPage = false,
        bool $isItem = false,
    ):self {
        return new self(
            type: $type,
            status: $status,
            className: $className,
            description: $description,
            example: $example,
            isPage: $isPage,
            isItem: $isItem,
        );
    }

    private array $response = [];

    /**
     *
     * @param  string               $type        http content-type
     * @param  int                  $status      http status code
     * @param  string               $description
     * @param  mixed                $example     an example of the body of the response
     * @param  bool                 $isPage      if set to true, the produced response will be wrapped in a page structure.
     * @param  bool                 $isItem      if set to true, the produced response will be wrapped in an item structure.
     * @param  string|array<string> $schema      a class name or an array 1 one single element, which must be a class name.
     * @return void
     */
    private function __construct(
        private string $type,
        private int $status,
        private string $className,
        private string $description,
        private mixed $example,
        private bool $isPage,
        private bool $isItem,
    ) {
        if ($isItem) {
            $converted     = is_array($this->example) || is_object($this->example)?(object)$this->example:$this->example;
            $this->example = (object)[
                "type"    => "item",
                "status"  => 200,
                "message" => "OK",
                "data"    => $converted,
            ];
        } else if ($isPage) {
            $converted     = is_array($this->example) || is_object($this->example)?(object)$this->example:$this->example;
            $this->example = (object)[
                "type"         => "page",
                "status"       => 200,
                "message"      => "OK",
                "previousHref" => "http://example.com?start0&size=3",
                "nextHref"     => "http://example.com?start6&size=3",
                "previous"     => [
                    "start" => 0,
                    "size"  => 3,
                ],
                "next" => [
                    "start" => 6,
                    "size"  => 3,
                ],
                "data" => [
                    $converted,
                ],
            ];
        }
    }

    public function getStatus():int {
        return $this->status;
    }

    public function getContentType():string {
        return $this->type;
    }

    public function getValue():array {
        return $this->response;
    }

    public function getClassName():string {
        return $this->className;
    }

    /**
     * 
     * @param  OpenApiService $oa
     * @return Unsafe<void>
     */
    #[Entry] public function setup(OpenApiService $oa):Unsafe {
        $isClass = class_exists($this->className);
        $type    = '';
        if ($isClass) {
            if ($this->isPage) {
                $type = 'Page';
                $oa->setComponentReferencePage($this->className);
            } else if ($this->isItem) {
                $type = 'Item';
                $oa->setComponentReferenceItem($this->className);
            }
            if ($error = $oa->setComponentObject($this->className)->error) {
                return error($error);
            }
        }
        
        if ($isClass) {
            $schema = [
                'type' => 'object',
                '$ref' => "#/components/schemas/$this->className$type",
            ];
        } else {
            if ('array' === $this->className) {
                $schema = [
                    'type'  => 'array',
                    'items' => [
                        'type' => 'string',
                    ],
                ];
            } else {
                $type = match ($this->className) {
                    'int'   => 'integer',
                    'float' => 'number',
                    'bool'  => 'boolean',
                    default => $this->className,
                };
                if ($this->isItem) {
                    $schema = OpenApiService::templateForItem( className: $type, dataIsObject: false );
                } else if ($this->isPage) {
                    $schema = OpenApiService::templateForPage( className: $type, dataIsObject: false );
                } else {
                    $schema = [
                        'type' => $type,
                    ];
                }
            }
        }

        $this->response = $oa->createResponse(
            status:$this->status,
            description: $this->description,
            contentType: $this->type,
            schema: $schema,
            example: $this->example,
        );

        return ok();
    }
}