<?php
namespace CatPaw\Core;

use SplObjectStorage;
use stdClass;

class StateContext {
    /** @var SplObjectStorage<object, stdClass> */
    private static SplObjectStorage $items;

    public static function get(object $key):stdClass {
        if (!isset(self::$items)) {
            self::$items = new SplObjectStorage();
        }
        return self::$items->offsetGet($key);
    }

    public static function set(object $key, stdClass $state):void {
        if (!isset(self::$items)) {
            self::$items = new SplObjectStorage();
        }
        self::$items->attach($key, $state);
    }

    public static function unset(object $key):void {
        if (!isset(self::$items)) {
            self::$items = new SplObjectStorage();
        }
        self::$items->detach($key);
    }

    private function __construct() {
    }
}
