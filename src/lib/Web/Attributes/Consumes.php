<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Attributes\Entry;
use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Traits\CoreAttributeDefinition;
use CatPaw\Web\ConsumedRequest;
use CatPaw\Web\Services\OpenApiService;


/**
 * Define the type of content the route handler consumes.
 * 
 * Some examples:
 * 
 * - `#[Consumes("string", "application/json")]`
 * - `#[Consumes("string", "text/plain")]`
 * 
 * ### Note
 * Specifically the type `"application/json"` will allow object and array mappings using `#[Body]`.
 * @see Body
 * @package CatPaw\Web\Attributes
 */
#[Attribute]
class Consumes implements AttributeInterface {
    use CoreAttributeDefinition;

    /** @var array<ConsumedRequest> */
    private array $request = [];
    

    /**
     * @param string|array $schema       usually `string`, but can also be a class name to indicate the structure of the content.
     * @param string|array $contentTypes the http content-type, like `application/json`, `text/html` etc.
     * @param mixed        $example
     */
    public function __construct(
        string|array $schema = 'string',
        string|array $contentTypes = 'application/json',
        mixed $example = '',
    ) {
        if (is_string($contentTypes)) {
            $contentTypes = [$contentTypes];
        }

        foreach ($contentTypes as $contentType) {
            $this->request[] = ConsumedRequest::create(
                type: $contentType,
                description: '',
                className: $schema,
                example: $example,
            );
        }
    }

    #[Entry] public function setup(OpenApiService $oa):void {
        foreach ($this->request as $request) {
            $request->setup($oa);
        }
    }

    /**
     * Get the types of content available to consume.
     *
     * @return array<string>
     */
    public function getContentType(): array {
        $contentType = [];
        foreach ($this->request as $request) {
            $contentType[] = $request->getContentType();
        }
        return $contentType;
    }

    /**
     * Get the shaped responses available to consume.
     *
     * @return array<ConsumedRequest>
     */
    public function getRequest():array {
        return $this->request;
    }
}
