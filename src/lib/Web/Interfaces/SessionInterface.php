<?php
namespace CatPaw\Web\Interfaces;

use Amp\Http\Server\Request;

interface SessionInterface {
    /**
     * Create a session from an http request.
     * @param  Request $request
     * @return self
     */
    public static function create(Request $request):self;
    /**
     * Apply required modifications to the response modifier, for example a `session-id` cookie.
     * > **Note**\
     * > You don't need to invoke this method yourself, the router will handle this automatically.
     * @param  ResponseModifier $modifier
     * @return void
     */
    public function apply(ResponseModifier $modifier):void;
    /**
     * Validate the session.
     * @return bool _True_ if the session exists, is not expired and has not been stopped, otherwise _false_.
     */
    public function validate():bool;
    /**
     * Check if key exists in session.
     * @param  string $key
     * @return bool
     */
    public function has(string $key): bool;
    /**
     * Create and return a reference to an item the session.\
     * If the `$key` already exists, return its relative item instead of creating a new one.
     * @template T
     * @param  string $key
     * @param  T      $default Default value to return if key is not found.
     * @return T      Reference to the item.
     */
    public function &ref(string $key, mixed $default = null):mixed;
    /**
     * Get the session id.
     * @return string
     */
    public function id():string;
    /**
     * Destroy the session.
     * @return void
     */
    public function destroy():void;
}
