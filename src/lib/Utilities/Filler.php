<?php

namespace CatPaw\Utilities;

class Filler {
    public const FILL_LEFT  = 0;
    public const FILL_RIGHT = 1;

    public static function fill(int $length, string|int|float $contents, int $fillDirection = 1, string $filler = ' '): string {
        if (is_float($contents)) {
            $contents = (int)$contents * 100;
        }

        $contents   = substr("$contents", 0, $length);
        $filler_str = str_repeat($filler, $length - strlen($contents));
        if ($fillDirection === static::FILL_LEFT) {
            return "$filler_str$contents";
        }
        return "$contents$filler_str";
    }
}