<?php
namespace CatPaw\Core;

class Provider {
    /** @var array<string,callable> */
    private static array $items = [];
    private function __construct() {
    }
    /**
     * Create a provider for a given class.
     * @param  string   $name
     * @param  callable $function
     * @return void
     * @internal
     */
    public static function set(string $name, callable $function):void {
        self::$items[$name] = $function;
    }

    public static function unset(string $name):void {
        unset(self::$items[$name]);
    }

    /**
     * Check if a given provider exists,
     * @param  string $className
     * @return bool
     * @internal
     */
    public static function exists(string $className):bool {
        return isset(self::$items[$className]);
    }

    /**
     * Get a given provider function.
     * @param  string         $className
     * @return false|callable
     * @internal
     */
    public static function get(string $className):false|callable {
        return self::$items[$className] ?? false;
    }

    /**
     * Clear all providers.\
     * Next time you create a new instance the cache will miss.
     * @return void
     * @internal
     */
    public static function clear():void {
        self::$items = [];
    }
}
