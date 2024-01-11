<?php

namespace CatPaw\Core;

class Filler {
    public const FILL_LEFT  = 0;
    public const FILL_RIGHT = 1;

    /**
     * @param  int              $length
     * @param  string|int|float $contents
     * @param  int              $fillDirection
     * @param  string           $filler
     * @return string
     */
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