<?php

namespace CatPaw\Store;

/**
 * @template T
 * @param T $value The initial value of the store
 * @deprecated in favor of `Writable::create()`.
 * @return Writable<T>
 */
function writable($value):Writable {
    return Writable::create($value);
}

/**
 *
 * @template T
 * @param  T                                        $value initial value of the store
 * @param  false|callable(callable):(void|callable) $start
 * @return Readable<T>
 * @deprecated in favor of `Readable::create()`.
 */
function readable(
    $value,
    false|callable $start = false,
):Readable {
    if (!$start) {
        $start = function() {
            return function() {
            };
        };
    }
    /** @var Readable<T> */
    return Readable::create($value, $start);
}
