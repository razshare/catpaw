<?php
namespace CatPaw\Web\Attributes;

use Attribute;
use CatPaw\Core\Attributes\Entry;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Core\Unsafe;

use CatPaw\Web\ConsumedRequest;
use CatPaw\Web\Interfaces\OpenApiStateInterface;

/**
 * Define the type of content the route handler consumes.
 *
 * ## Example
 *
 * ```php
 * use CatPaw\Web\Attributes\Consumes;
 * use CatPaw\Web\Attributes\Produces;
 * use function CatPaw\Web\success;
 *
 * #[Consumes('text/plain', 'string', 'this is an example')]
 * #[Produces(200, 'text/plain', 'On success.', 'string')]
 * function myRouteHandler(#[Body] string $message) {
 *  return success("Message: $message");
 * }
 * ```
 *
 * ### Note
 * Specifically the type `"application/json"` will allow object and array mappings using `#[Body]`.
 * @see Body
 * @package CatPaw\Web\Attributes
 */
#[Attribute(flags:Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Consumes implements AttributeInterface {
    use CoreAttributeDefinition;

    /** @var array<ConsumedRequest> */
    private array $request = [];


    /**
     * @param string $contentType the http content-type, like `application/json`, `text/html` etc.
     * @param string $schema      usually `string`, but can also be a class name to indicate the structure of the content.
     * @param mixed  $example
     */
    public function __construct(
        string $contentType,
        string $schema,
        mixed $example = '',
    ) {
        $this->request[] = ConsumedRequest::create(
            className  : $schema,
            type       : $contentType,
            example    : $example,
        );
    }

    /**
     *
     * @param  OpenApiStateInterface $openApiState
     * @return Unsafe<None>
     */
    #[Entry] public function setup(OpenApiStateInterface $openApiState):Unsafe {
        foreach ($this->request as $request) {
            $request->setup($openApiState)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }
        return ok();
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
