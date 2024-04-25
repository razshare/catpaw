<?php
namespace CatPaw\Text;

use function join;

/**
 * @return string
 */
function nocolor():string {
    return "\033[0m";
}

/**
 * @param  int    $red
 * @param  int    $green
 * @param  int    $blue
 * @return string
 */
function background(
    int $red = 0,
    int $green = 0,
    int $blue = 0,
):string {
    $red   %= 256;
    $green %= 256;
    $blue  %= 256;
    return "\033[48;2;$red;$green;{$blue}m";
}

/**
 * @param  int    $red
 * @param  int    $green
 * @param  int    $blue
 * @return string
 */
function foreground(
    int $red = 0,
    int $green = 0,
    int $blue = 0,
):string {
    $red   %= 256;
    $green %= 256;
    $blue  %= 256;
    return "\033[38;2;$red;$green;{$blue}m";
}

/**
 * Returns a combination of string that will clear the terminal.
 * @return string
 */
function clear():string {
    return join([nocolor(),"\033c"]);
}

/**
 * Create some colorful text.
 * @param  string           $value
 * @param  array<int>|false $foregroundRGB
 * @param  array<int>|false $backgroundRGB
 * @return string
 */
function text(string $value, array|false $foregroundRGB = false, array|false $backgroundRGB = false):string {
    return join([
        $foregroundRGB?foreground(...$foregroundRGB):'',
        $backgroundRGB?background(...$backgroundRGB):'',
        $value,
        nocolor(),
    ]);
}
