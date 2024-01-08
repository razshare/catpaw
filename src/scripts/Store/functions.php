<?php

namespace CatPaw\Store;

use Closure;

/**
 * @param  mixed    $value The initial value of the store
 * @return Writable
 */
function writable(mixed $value): Writable {
    return Writable::create($value);
}


/**
 * @param  mixed                       $value   initial value of the store
 * @param  false|(callable():callable) $onStart a function that will be executed when the 
 *                                              first subscriber subscribes to the store.
 *                                              The function must return another function, which 
 *                                              will be executed when the last subscriber of the store unsubscribes.
 * @return Readable
 */
function readable(
    mixed $value,
    false|Closure $start = false,
): Readable {
    if (!$start) {
        $start = fn ():callable => fn () => null;
    }
    return Readable::create($value, $start);
}
