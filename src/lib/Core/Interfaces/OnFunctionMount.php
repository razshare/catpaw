<?php

namespace CatPaw\Core\Interfaces;

use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\Unsafe;
use Closure;
use ReflectionFunction;

interface OnFunctionMount {
    /**
     * Invoked when this attribute is detected on a function.
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw-core/blob/main/docs/9.Filters.md
     * @param  ReflectionFunction  $reflection reflection of the function.
     * @param  Closure             $value      function to which this attribute is attached to.
     * @param  DependenciesOptions $options    options used to find dependencies.
     * @return Unsafe<void>
     */
    public function onFunctionMount(ReflectionFunction $reflection, Closure &$value, DependenciesOptions $options):Unsafe;
}
