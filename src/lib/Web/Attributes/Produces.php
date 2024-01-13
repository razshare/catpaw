<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Attributes\Entry;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use function CatPaw\Core\ok;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;

use CatPaw\Web\ProducedResponse;
use CatPaw\Web\Services\OpenApiService;

/**
 * Define the type of content the route handler produces.
 * 
 * Some examples:
 * 
 * - `#[Produces("string","application/json")]`
 * - `#[Produces("string", ["application/json", "application/xml"])]`
 * 
 * ### Note
 * Specifically the type `"application/json"` will serialize objects and arrays into JSON.
 * @see Body
 * @package CatPaw\Web\Attributes
 */
#[Attribute]
class Produces implements AttributeInterface {
    use CoreAttributeDefinition;

    /** @var array<ProducedResponse> */
    protected array $response      = [];
    protected bool $isPrimitive    = true;
    protected bool $isItem         = false;
    protected bool $isPage         = false;
    protected bool $hasContentType = false;
    
    /**
     * @param string|array $className   usually `string`, but can also be a class name to indicate the structure of the content.
     * @param string|array $contentType the http content-type, like `application/json`, `text/html` etc.
     * @param mixed        $example
     */
    public function __construct(
        protected string|array $className = 'string',
        protected string|array $contentType = '',
        protected mixed $example = '',
    ) {
        $this->setContentType($contentType);
    }

    protected function createProducedResponse(
        string|array $className,
        string|array $contentType,
        mixed $example,
    ):ProducedResponse {
        return ProducedResponse::create(
            type: $contentType,
            className: $className,
            example: $example,
        );
    }

    #[Entry] public function setup(OpenApiService $oa):Unsafe {
        foreach ($this->response as $response) {
            $response->setup($oa)->try($error);
            if ($error) {
                return error($error);
            }
        }
        return ok();
    }

    public function hasContentType():bool {
        return $this->hasContentType;
    }

    public function isItem():bool {
        return $this->isItem;
    }

    public function isPage():bool {
        return $this->isPage;
    }

    public function isPrimitive():bool {
        return $this->isPrimitive;
    }

    public function setContentType(string|array $contentType):void {
        $this->response = [];
        if (is_string($contentType)) {
            $contentType = [$contentType];
        }
        
        foreach ($contentType as $contentTypeItem) {
            if (!$this->hasContentType && $contentTypeItem) {
                $this->hasContentType = true;
            }
            if (
                $this->isPrimitive
                && (
                    'application/json'   === $contentTypeItem
                    || 'application/xml' === $contentTypeItem
                )
            ) {
                $this->isPrimitive = false;
            }

            $this->response[] = $this->createProducedResponse(
                className: $this->className,
                contentType: $contentTypeItem,
                example: $this->example,
            );
        }
    }

    /**
     * Get the types of content available to generate.
     *
     * @return array<string>
     */
    public function getContentType(): array {
        $contentType = [];
        foreach ($this->response as $response) {
            $contentType[] = $response->getContentType();
        }
        return $contentType;
    }

    /**
     * Get the shaped responses available to generate.
     *
     * @return array<ProducedResponse>
     */
    public function getResponse():array {
        return $this->response;
    }
}
