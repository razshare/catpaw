<?php
namespace CatPaw\Traits;

trait create {
    public static function create(false|callable $callback = false):self {
        $instance = new self;
        if (!$callback) {
            return $instance;
        }
        $callback($instance);
        return $instance;
    }
}