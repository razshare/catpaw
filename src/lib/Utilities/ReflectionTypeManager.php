<?php
namespace CatPaw\Utilities;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;

class ReflectionTypeManager {
    private function __construct() {
    }

    public static function unwrap(ReflectionParameter|ReflectionProperty $parameter):ReflectionNamedType|null {
        $type = $parameter->getType() ?? null;
        if (null === $type) {
            return null;
        }
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $types = $type->getTypes();
            $type  = $types[0];
            foreach ($types as $i => $t) {
                if ('null' !== $t && 'false' !== $t) {
                    /** @var ReflectionNamedType */
                    return $t;
                }
            }
        }
        /** @var ReflectionNamedType */
        return $type;
    }
}