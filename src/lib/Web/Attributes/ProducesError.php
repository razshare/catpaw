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
use CatPaw\Web\ErrorItem;
use CatPaw\Web\Interfaces\OpenApiStateInterface;
use CatPaw\Web\ProducedResponse;

/**
 * Describe the type of content the route handler produces so that the `OpenApiInterface` can handle it.
 *
 * ## Example
 *
 * ```php
 * use CatPaw\Web\Attributes\ProducesError;
 * use function CatPaw\Web\failure;
 *
 * #[ProducesError(404, 'text/plain', "When the item can't be found.")]
 * function myRouteHandler() {
 *  return failure('Item not found.', 404);
 * }
 * ```
 *
 * @see Body
 * @package CatPaw\Web\Attributes
 */
#[Attribute(flags:Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ProducesError implements AttributeInterface {
    use CoreAttributeDefinition;
    // @phpstan-ignore-next-line
    private static string $errorClassName = ErrorItem::class;
    /** @var array<ProducesError> */
    private static array $producers = [];
    public static function withErrorClassName(string $className):void {
        self::$errorClassName = $className;
        foreach (self::$producers as $producesError) {
            $producesError->update();
        }
    }

    private Produces $produces;

    /**
     * @param int    $status      status code of the response.
     * @param string $contentType the http content-type, like `application/json`, `text/html` etc.
     * @param string $description describe when this error is produced.
     */
    public function __construct(
        private int $status,
        private string $contentType,
        private string $description,
    ) {
        $this->update();
        self::$producers[] = $this;
    }

    public function update():void {
        $this->produces = new Produces(
            status: $this->status,
            className: 'string',
            contentType: $this->contentType,
            description: $this->description,
            example: [],
        );
    }

    /**
     *
     * @param  OpenApiStateInterface $openApiState
     * @return Result<None>
     */
    #[Entry] public function start(OpenApiStateInterface $openApiState):Result {
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
