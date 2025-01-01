<?php

namespace CatPaw\Core\Interfaces;

use CatPaw\Core\ContainerContext;
use CatPaw\Core\None;
use CatPaw\Core\Result;
use Closure;
use ReflectionFunction;

interface OnFunctionMountInterface {
    /**
     * Invoked when this attribute is detected on a function.
     * @see https://github.com/tncrazvan/catpaw/blob/main/docs/1.RouteHandlers.md
     * @see https://github.com/tncrazvan/catpaw/blob/main/docs/9.Filters.md
     * @param  ReflectionFunction $reflectionFunction Reflection of the function.
     * @param  Closure            $value              Function to which this attribute is attached to.
     * @param  ContainerContext   $options            Options used to find dependencies.
     * @return Result<None>
     */
    public function onFunctionMount(ReflectionFunction $reflectionFunction, Closure &$value, ContainerContext $options):Result;
}
