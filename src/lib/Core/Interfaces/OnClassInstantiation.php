<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Unsafe;
use ReflectionClass;

interface OnClassInstantiation {
    /**
     * Invoked when an instance of this class is created through `Container::get`.
     * @param  ReflectionClass<object> $reflection   Reflection of the class.
     * @param  mixed                   $instance     Instance of the class.
     * @param  array<int,mixed>        $dependencies Arguments for the constructor.\
     *                                               These are provided by the `Container`.
     * @return Unsafe<None>
     */
    public function onClassInstantiation(ReflectionClass $reflection, mixed &$instance, array $dependencies):Unsafe;
}
