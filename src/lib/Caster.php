<?php

namespace CatPaw;

use function get_object_vars;
use function is_array;

class Caster {
    private function __construct() {
    }
    /**
     * Cast an \stdClass object to a specific classname.
     * @param  mixed  $obj       the object to cast.
     * @param  string $className the name of the class you want to cast the $object as.
     * @return mixed  the newly cast object.
     */
    public static function cast(array|object $obj, string $className): mixed {
        if (!$obj) {
            return $obj;
        }
        if ('object' === $className) {
            $result = (object)[];
        } else if ('object' === $className || 'stdClass' === $className) {
            return (object)[];
        }

        if (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $result->$key = $value;
            }
        } else {
            $props = get_object_vars($obj);
            foreach ($props as $key => $value) {
                $result->$key = $value;
            }
        }
        return $result;
    }
}