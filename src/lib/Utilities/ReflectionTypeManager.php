<?php
namespace CatPaw\Utilities;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

class ReflectionTypeManager {
    public static function unwrap(ReflectionParameter $parameter):ReflectionNamedType|ReflectionType|null {
        $type = $parameter->getType() ?? null;
        if (null === $type) {
            return null;
        }
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $types = $type->getTypes();
            $type  = $types[0];
            foreach ($types as $i => $t) {
                if ('null' !== $t && 'false' !== $t) {
                    return $t;
                }
            }
        }
        return $type;
    }
}