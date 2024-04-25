<?php
namespace CatPaw\Core;

abstract class IntConverter {
    private function __construct() {
    }

    public static function int8(mixed $i):mixed {
        return is_int($i) ? pack("c", $i) : unpack("c", $i)[1];
    }

    public static function uInt8(mixed $i):mixed {
        return is_int($i) ? pack("C", $i) : unpack("C", $i)[1];
    }

    public static function int16(mixed $i):mixed {
        return is_int($i) ? pack("s", $i) : unpack("s", $i)[1];
    }

    public static function uInt16(mixed $i, null|bool $endian = false):mixed {
        $f = is_int($i) ? "pack" : "unpack";

        if (true === $endian) {  // big-endian
            $i = $f("n", $i);
        } else {
            if (false === $endian) {  // little-endian
                $i = $f("v", $i);
            } else {
                if (null === $endian) {  // machine byte order
                    $i = $f("S", $i);
                }
            }
        }

        return is_array($i) ? $i[1] : $i;
    }

    public static function int32(mixed $i):mixed {
        return is_int($i) ? pack("l", $i) : unpack("l", $i)[1];
    }

    public static function uInt32(mixed $i, null|bool $endian = false):mixed {
        $f = is_int($i) ? "pack" : "unpack";

        if (true === $endian) {  // big-endian
            $i = $f("N", $i);
        } else {
            if (false === $endian) {  // little-endian
                $i = $f("V", $i);
            } else {
                if (null === $endian) {  // machine byte order
                    $i = $f("L", $i);
                }
            }
        }

        return is_array($i) ? $i[1] : $i;
    }

    public static function int64(mixed $i):mixed {
        return is_int($i) ? pack("q", $i) : unpack("q", $i)[1];
    }

    public static function uInt64(mixed $i, null|bool $endian = false):mixed {
        $f = is_int($i) ? "pack" : "unpack";

        if (true === $endian) {  // big-endian
            $i = $f("J", $i);
        } else {
            if (false === $endian) {  // little-endian
                $i = $f("P", $i);
            } else {
                if (null === $endian) {  // machine byte order
                    $i = $f("Q", $i);
                }
            }
        }

        return is_array($i) ? $i[1] : $i;
    }
}
