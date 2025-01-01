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
 * function myRouteHandler(Body $body) {
 *  return success("Message: {$body->as(string::class)}");
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
     * @param string $className   usually `string`, but can also be a class name to indicate the structure of the content.
     * @param mixed  $example
     */
    public function __construct(
        string $contentType,
        string $className,
        mixed $example = '',
    ) {
        $this->request[] = new ConsumedRequest(
            className  : $className,
            type       : $contentType,
            example    : $example,
        );
    }

    /**
     *
     * @param  OpenApiStateInterface $openApiState
     * @return Result<None>
     */
    #[Entry] public function start(OpenApiStateInterface $openApiState):Result {
        foreach ($this->request as $request) {
            $request->start($openApiState)->unwrap($error);
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
    public function contentType():array {
        $contentType = [];
        foreach ($this->request as $request) {
            $contentType[] = $request->contentType();
        }
        return $contentType;
    }

    /**
     * Get the shaped responses available to consume.
     *
     * @return array<ConsumedRequest>
     */
    public function request():array {
        return $this->request;
    }
}
