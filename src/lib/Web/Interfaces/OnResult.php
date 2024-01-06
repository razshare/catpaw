<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Unsafe;
use React\Http\Message\Request;

/**
 * Attach this to route handlers in order intercept their events.
 * @package CatPaw\Web\Interfaces
 */
interface OnResult extends AttributeInterface {
    /**
     * Invoked after a route is executed.
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/9.Filters.md
     * @param  Request      $request this is the incoming request.
     * @param  mixed        $result  this is the result of the route.
     *                               > **NOTE**\
     *                               > This is the immediate `result` of the route handler, not the actual response served to the client.\
     *                               > Later down the chain this `result` will be modified by the server in order to attempt 
     *                               > sattisfying the client's `Accept` header according to the server's `Content-Type`.
     * @return Unsafe<void>
     */
    public function onResult(Request $request, mixed &$result):Unsafe;
}