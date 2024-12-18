<?php

namespace CatPaw\Core\Interfaces;

use CatPaw\Core\DependenciesOptions;
use CatPaw\Core\None;
use CatPaw\Core\Result;
use ReflectionParameter;

interface OnParameterMountInterface {
    /**
     * Invoked when this attribute is detected on a parameter.
     * @param  ReflectionParameter $reflectionParameter Reflection of the parameter.
     * @param  mixed               $value               Current value of the parameter.
     * @param  DependenciesOptions $options             Options used to find dependencies.
     * @return Result<None>
     */
    public function onParameterMount(ReflectionParameter $reflectionParameter, mixed &$value, DependenciesOptions $options):Result;
}
