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
 * @param  mixed         $value initial value of the store
 * @param  false|Closure $start
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
