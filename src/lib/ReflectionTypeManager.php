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
        $className = ReflectionTypeManager::unwrap($reflection)?->getName() ?? $defaultClassName;

        $rtype = $reflection->getType();

        $classNames = [$className];

        if ($rtype instanceof ReflectionUnionType || $rtype instanceof ReflectionIntersectionType) {
            $classNames = $rtype->getTypes();
            foreach ($classNames as $i => $t) {
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

    public static function unwrap(ReflectionParameter|ReflectionProperty $parameter): ReflectionNamedType|null {
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
