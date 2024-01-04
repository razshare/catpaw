<?php

namespace CatPaw\Interfaces;

use CatPaw\DependenciesOptions;
use CatPaw\Unsafe;
use ReflectionParameter;

interface OnParameterMount {
    /**
     * Invoked when this attribute is detected on a parameter.
     * @param  ReflectionParameter $reflection reflection of the parameter.
     * @param  mixed               $value      current value of the parameter.
     * @param  DependenciesOptions $options    options used to find dependencies.
     * @return Unsafe<void>
     */
    public function onParameterMount(ReflectionParameter $reflection, mixed &$value, DependenciesOptions $options):Unsafe;
}
