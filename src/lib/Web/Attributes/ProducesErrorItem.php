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
use CatPaw\Web\ErrorItem;
use CatPaw\Web\Interfaces\OpenApiStateInterface;
use CatPaw\Web\ProducedResponse;

/**
 * Describe the type of content the route handler produces so that the `OpenApiInterface` can handle it.
 *
 * ## Example
 *
 * ```php
 * use CatPaw\Web\Attributes\ProducesErrorItem;
 * use function CatPaw\Web\failure;
 *
 * #[ProducesErrorItem(404, 'application/json', "When the item can't be found.")]
 * function myRouteHandler() {
 *  return
 *      failure('Item not found.', 404)
 *          ->as('application/json')
 *              ->item();
 * }
 * ```
 *
 * @see Body
 * @package CatPaw\Web\Attributes
 */
#[Attribute(flags:Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ProducesErrorItem implements AttributeInterface {
    use CoreAttributeDefinition;
    private static string $errorClassName = ErrorItem::class;
    /** @var array<ProducesErrorItem> */
    private static array $producers = [];
    public static function setErrorClassName(string $className):void {
        self::$errorClassName = $className;
        foreach (self::$producers as $producesErrorItem) {
            $producesErrorItem->update();
        }
    }

    private Produces $produces;

    /**
     * @param int    $status      status code of the response.
     * @param string $contentType the http content-type, like `application/json`, `text/html` etc.
     * @param string $description describe when this error item is produced.
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
            isErrorItem: true,
            errorClassName: self::$errorClassName,
        );
    }

    /**
     *
     * @param  OpenApiStateInterface $openApiState
     * @return Unsafe<None>
     */
    #[Entry] public function setup(OpenApiStateInterface $openApiState): Unsafe {
        foreach ($this->produces->getResponse() as $response) {
            $response->setup($openApiState)->unwrap($error);
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
