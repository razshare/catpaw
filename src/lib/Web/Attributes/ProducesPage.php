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
 * Describe the type of content the route handler produces so that the `OpenApiService` can handle it.
 *
 * ## Example
 *
 * ```php
 * use CatPaw\Web\Page;
 * use CatPaw\Web\Attributes\ProducesPage;
 * use function CatPaw\Web\success;
 *
 * #[ProducesPage(200, 'application/json', 'On success.', 'string', 'this is an example')]
 * function myRouteHandler(Page $page) {
 *  return
 *      success(['item-1','item-2','item-3'])
 *          ->as('application/json')
 *              ->page($page);
 * }
 * ```
 *
 * @see Body
 * @package CatPaw\Web\Attributes
 */
#[Attribute(flags:Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ProducesPage implements AttributeInterface {
    use CoreAttributeDefinition;

    private Produces $produces;

    /**
     * @param int    $status      status code of the response.
     * @param string $contentType the http content-type, like `application/json`, `text/html` etc.
     * @param string $description describe when this page is produced.
     * @param string $className   usually `string`, but can also be a class name to indicate the structure of the content.
     * @param mixed  $example
     */
    public function __construct(
        private int $status,
        private string $contentType,
        private string $description,
        private string $className,
        private mixed $example = []
    ) {
        $this->produces = new Produces(
            status: $status,
            className: $className,
            contentType: $contentType,
            description: $description,
            example: $example,
            isPage: true,
        );
    }

    #[Entry] public function setup(OpenApiService $oa): Unsafe {
        foreach ($this->produces->getResponse() as $response) {
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
        foreach ($this->produces->getResponse() as $response) {
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
        return $this->produces->getResponse();
    }

    public function getProduces():Produces {
        return $this->produces;
    }
}
