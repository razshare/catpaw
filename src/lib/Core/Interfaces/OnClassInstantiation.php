<?php
namespace CatPaw\Interfaces;

use CatPaw\Unsafe;
use ReflectionClass;

interface OnClassInstantiation {
    /**
     * Invoked when this attribute is detected on a class.
     * @param  ReflectionClass $reflection   reflection of the class.
     * @param  mixed           $instance     instance of the class.
     * @param  array           $dependencies arguments for class the constructor.
     * @return Unsafe<void>
     */
    public function onClassInstantiation(ReflectionClass $reflection, mixed &$instance, array $dependencies):Unsafe;
}