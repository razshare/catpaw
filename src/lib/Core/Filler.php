<?php

namespace CatPaw\Core;

class Filler {
    public const FILL_LEFT  = 0;
    public const FILL_RIGHT = 1;

    /**
     * @param  int              $length
     * @param  string|int|float $content
     * @param  int              $fillDirection
     * @param  string           $filler
     * @return string
     */
    public static function fill(int $length, string|int|float $content, int $fillDirection = 1, string $filler = ' '): string {
        if (is_float($content)) {
            $content = (int)$content * 100;
        }

        $content    = substr("$content", 0, $length);
        $filler_str = str_repeat($filler, $length - strlen($content));
        if ($fillDirection === static::FILL_LEFT) {
            return "$filler_str$content";
        }
        return "$content$filler_str";
    }
}