<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Attributes\Entry;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Web\Interfaces\OpenApiStateInterface;
use CatPaw\Web\ProducedResponse;

/**
 * Describe the type of content the route handler produces so that the `OpenApiInterface` can handle it.
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
        // @phpstan-ignore-next-line
        private int $status,
        // @phpstan-ignore-next-line
        private string $contentType,
        // @phpstan-ignore-next-line
        private string $description,
        // @phpstan-ignore-next-line
        private string $className,
        // @phpstan-ignore-next-line
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

    /**
     *
     * @param  OpenApiStateInterface $openApiState
     * @return Result<None>
     */
    #[Entry] public function start(OpenApiStateInterface $openApiState): Result {
        foreach ($this->produces->response() as $response) {
            $response->start($openApiState)->unwrap($error);
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
    public function contentType():array {
        $contentType = [];
        foreach ($this->produces->response() as $response) {
            $contentType[] = $response->contentType();
        }
        return $contentType;
    }

    /**
     * Get the shaped responses available to generate.
     *
     * @return array<ProducedResponse>
     */
    public function response():array {
        return $this->produces->response();
    }

    public function produces():Produces {
        return $this->produces;
    }
}
