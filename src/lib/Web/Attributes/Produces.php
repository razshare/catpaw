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
 * use CatPaw\Web\Attributes\Produces;
 * use function CatPaw\Web\success;
 *
 * #[Produces(200, 'text/plain', 'On success.', 'string', 'this is an example')]
 * function myRouteHandler() {
 *  return success('hello world');
 * }
 * ```
 *
 * @see Body
 * @package CatPaw\Web\Attributes
 */
#[Attribute(flags:Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Produces implements AttributeInterface {
    use CoreAttributeDefinition;

    /** @var array<ProducedResponse> */
    protected array $response      = [];
    protected bool $isPrimitive    = true;
    protected bool $hasContentType = false;

    /**
     *
     * @param  int    $status         status code of the response.
     * @param  string $contentType    the http content-type, like `application/json`, `text/html` etc.
     * @param  string $description    describe when this content is produced.
     * @param  string $className      usually `string`, but can also be a class name to indicate the structure of the content.
     * @param  mixed  $example
     * @param  bool   $isItem
     * @param  bool   $isPage
     * @param  bool   $isErrorItem
     * @param  string $errorClassName
     * @return void
     */
    public function __construct(
        protected int $status,
        protected string $contentType,
        protected string $description,
        protected string $className,
        protected mixed $example = '',
        protected bool $isItem = false,
        protected bool $isPage = false,
        protected bool $isErrorItem = false,
        protected string $errorClassName = ErrorItem::class,
    ) {
        $this->withContentType($contentType);
    }

    protected function createProducedResponse():ProducedResponse {
        return new ProducedResponse(
            status: $this->status,
            type: $this->contentType,
            className: $this->className,
            example: $this->example,
            isItem: $this->isItem,
            isPage: $this->isPage,
            isErrorItem: $this->isErrorItem,
            description: $this->description,
        );
    }

    /**
     *
     * @param  OpenApiStateInterface $openApiState
     * @return Result<None>
     */
    #[Entry] public function start(OpenApiStateInterface $openApiState):Result {
        foreach ($this->response as $response) {
            $response->start($openApiState)->unwrap($error);
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

    /**
     *
     * @param  string|array<string> $contentType
     * @return void
     */
    public function withContentType(string|array $contentType):void {
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

            $this->response[] = $this->createProducedResponse();
        }
    }

    /**
     * Get the types of content available to generate.
     *
     * @return array<string>
     */
    public function contentType():array {
        $contentType = [];
        foreach ($this->response as $response) {
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
        return $this->response;
    }
}
