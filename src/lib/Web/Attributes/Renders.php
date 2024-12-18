<?php
namespace CatPaw\Web\Attributes;

use Amp\Http\Server\Request;
use Attribute;
use CatPaw\Core\Interfaces\AttributeInterface;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Core\Traits\CoreAttributeDefinition;
use CatPaw\Web\Interfaces\OnRequestInterface;
use CatPaw\Web\Interfaces\OnResponseInterface;
use function CatPaw\Web\success;
use const CatPaw\Web\TEXT_HTML;

/**
 * ```
 * @package CatPaw\Web\Attributes
 */
#[Attribute(Attribute::TARGET_FUNCTION)]
class Renders implements AttributeInterface, OnRequestInterface, OnResponseInterface {
    use CoreAttributeDefinition;

    public function __construct(
        private string $contentType = TEXT_HTML
    ) {
    }

    public function onRequest(Request $request): Result {
        ob_start();
        return ok();
    }

    public function onResponse(Request $request, mixed &$response): Result {
        if (null === $response) {
            $response = ob_get_contents();
        }
        ob_end_clean();
        $response = success($response)->as($this->contentType);
        return ok();
    }
}