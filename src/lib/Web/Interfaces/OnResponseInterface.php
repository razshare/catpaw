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
interface OnResponseInterface extends AttributeInterface {
    /**
     * Invoked after a route is executed.
     * @see https://github.com/tncrazvan/catpaw/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw/blob/main/docs/9.Filters.md
     * @param  Request      $request   this is the incoming request.
     * @param  mixed        &$response this is the result of the route handler.
     * @return Result<None>
     */
    public function onResponse(Request $request, mixed &$response):Result;
}
