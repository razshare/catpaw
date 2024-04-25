<?php

namespace CatPaw\Core\Interfaces;

use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\None;
use CatPaw\Core\Unsafe;
use ReflectionParameter;

interface OnParameterMount {
    /**
     * Invoked when this attribute is detected on a parameter.
     * @param  ReflectionParameter $reflection reflection of the parameter.
     * @param  mixed               $value      current value of the parameter.
     * @param  DependenciesOptions $options    options used to find dependencies.
     * @return Unsafe<None>
     */
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe;
}
