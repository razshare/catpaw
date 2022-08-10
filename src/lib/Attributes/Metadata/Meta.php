<?php

namespace CatPaw\Attributes\Metadata;

class Meta {
    public static array $FUNCTION = [];

    public static function reset(): void {
        static::$FUNCTION = [];
    }
}