<?php

namespace CatPaw;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;

class ReflectionTypeManager {
    private function __construct() {
    }

    public static function wrap(ReflectionParameter|ReflectionProperty $reflection, string $defaultClassName = 'bool'): WrappedType {
        $reflectionType = ReflectionTypeManager::unwrap($reflection);
        $className      = $reflectionType?$reflectionType->getName(): $defaultClassName;

        $rtype = $reflection->getType();

        $classNames = [$className];

        if ($rtype instanceof ReflectionUnionType || $rtype instanceof ReflectionIntersectionType) {
            $classNames = $rtype->getTypes();
            foreach ($classNames as $t) {
                /**
                 * @psalm-suppress PossiblyUndefinedMethod
                 */
                $classNames[] = $t->getName();
            }
        }

        $allowsBoolean      = in_array('bool', $classNames);
        $allowsTrue         = in_array('true', $classNames);
        $allowsFalse        = in_array('false', $classNames);
        $allowsNullValue    = $reflection->allowsNull();
        $allowsDefaultValue = $reflection->isDefaultValueAvailable();
        $defaultValue       = $allowsDefaultValue ? $reflection->getDefaultValue() : null;

        return new WrappedType(
            allowsBoolean: $allowsBoolean,
            allowsTrue: $allowsTrue,
            allowsFalse: $allowsFalse,
            allowsNullValue: $allowsNullValue,
            allowsDefaultValue: $allowsDefaultValue,
            defaultValue: $defaultValue,
            className: $className,
        );
    }

    public static function unwrap(ReflectionParameter|ReflectionProperty $parameter): ReflectionNamedType|false {
        $type = $parameter->getType() ?? false;
        if (false === $type) {
            return false;
        }
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $types = $type->getTypes();
            $type  = $types[0];
            foreach ($types as $t) {
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
