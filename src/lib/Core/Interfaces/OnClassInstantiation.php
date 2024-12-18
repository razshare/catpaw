<?php
namespace CatPaw\Core\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;
use ReflectionClass;

interface OnClassInstantiation {
    /**
     * Invoked when an instance of this class is created through `Container::get`.
     * @param  ReflectionClass<object> $reflectionClass Reflection of the class.
     * @param  mixed                   $instance        Instance of the class.
     * @param  array<int,mixed>        $dependencies    Arguments for the constructor.\
     *                                                  These are provided by the `Container`.
     * @return Result<None>
     */
    public function onClassInstantiation(ReflectionClass $reflectionClass, mixed &$instance, array $dependencies):Result;
}
