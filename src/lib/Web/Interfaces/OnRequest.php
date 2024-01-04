<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Interfaces\AttributeInterface;
use CatPaw\Unsafe;
use React\Http\Message\Request;

/**
 * Attach this to route handlers in order intercept their events.
 * @package CatPaw\Web\Interfaces
 */
interface OnRequest extends AttributeInterface {
    /**
     * Invoked before a route is executed.
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/9.Filters.md
     * @param  Request      $request this is the incoming request.
     * @return Unsafe<void>
     */
    public function onRequest(Request $request):Unsafe;
}