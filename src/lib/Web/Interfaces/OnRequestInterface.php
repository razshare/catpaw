<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Request;
use CatPaw\Core\Interfaces\AttributeInterface;
use CatPaw\Core\None;
use CatPaw\Core\Result;

/**
 * Attach this to route handlers in order intercept their events.
 * @package CatPaw\Web\Interfaces
 */
interface OnRequestInterface extends AttributeInterface {
    /**
     * Invoked before a route is executed.
     * @see https://github.com/tncrazvan/catpaw/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw/blob/main/docs/9.Filters.md
     * @param  Request      $request this is the incoming request.
     * @return Result<None>
     */
    public function onRequest(Request $request):Result;
}
