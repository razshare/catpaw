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
class ProducesPage extends Produces implements AttributeInterface {
    use CoreAttributeDefinition;
    
    /**
     * @param string|array $className   usually `string`, but can also be a class name to indicate the structure of the content.
     * @param string|array $contentType the http content-type, like `application/json`, `text/html` etc.
     * @param mixed        $example
     */
    public function __construct(
        string|array $className = 'string',
        string|array $contentType = 'application/json',
        mixed $example = []
    ) {
        parent::__construct($className, $contentType, $example);
        $this->isPage = true;
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
            isPage: true,
        );
    }

    #[Entry] public function setup(OpenApiService $oa): Unsafe {
        foreach ($this->response as $response) {
            $response->setup($oa)->try($error);
            if ($error) {
                return error($error);
            }
        }
        return ok();
    }

    /**
     * Get the types of content available to generate.
     *
     * @return array<string>
     */
    public function getContentType():array {
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
