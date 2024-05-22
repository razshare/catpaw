<?php

namespace CatPaw\Core\Interfaces;

use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\None;
use CatPaw\Core\Unsafe;
use Closure;
use ReflectionFunction;

interface OnFunctionMount {
    /**
     * Invoked when this attribute is detected on a function.
     * @see https://github.com/tncrazvan/catpaw/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw/blob/main/docs/9.Filters.md
     * @param  ReflectionFunction  $reflection Reflection of the function.
     * @param  Closure             $value      Function to which this attribute is attached to.
     * @param  DependenciesOptions $options    Options used to find dependencies.
     * @return Unsafe<None>
     */
    public function onFunctionMount(ReflectionFunction $reflection, Closure &$value, DependenciesOptions $options):Unsafe;
}
