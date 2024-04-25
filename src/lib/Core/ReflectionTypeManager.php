<?php

namespace CatPaw\Core;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

class ReflectionTypeManager {
    private function __construct() {
    }

    /**
     * @param  ReflectionParameter|ReflectionProperty $reflection
     * @param  string                                 $defaultClassName
     * @return WrappedType
     */
    public static function wrap(ReflectionParameter|ReflectionProperty $reflection, string $defaultClassName = 'bool'): WrappedType {
        $reflectionType = ReflectionTypeManager::unwrap($reflection);
        $className      = $reflectionType?$reflectionType->getName(): $defaultClassName;

        $rtype = $reflection->getType();

        $classNames = [$className];

        if ($rtype instanceof ReflectionUnionType || $rtype instanceof ReflectionIntersectionType) {
            $classNames = $rtype->getTypes();
            foreach ($classNames as $t) {
                // @phpstan-ignore-next-line
                $classNames[] = $t->getName();
            }
        }

        $allowsBoolean      = in_array('bool', $classNames);
        $allowsTrue         = in_array('true', $classNames);
        $allowsFalse        = in_array('false', $classNames);
        $allowsNullValue    = $reflection->allowsNull();
        $allowsDefaultValue = $reflection->isDefaultValueAvailable();
        try {
            $defaultValue = $allowsDefaultValue ? $reflection->getDefaultValue() : null;
        } catch(Throwable) {
            $defaultValue = null;
        }

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

    /**
     * @param  ReflectionParameter|ReflectionProperty|ReflectionType $subject
     * @return ReflectionNamedType|false
     */
    public static function unwrap(ReflectionParameter|ReflectionProperty|ReflectionType $subject): ReflectionNamedType|false {
        if ($subject instanceof ReflectionType) {
            $type = $subject;
        } else {
            $type = $subject->getType() ?? false;
        }
        if (false === $type) {
            return false;
        }
        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $types = $type->getTypes();
            $type  = $types[0];
            foreach ($types as $t) {
                // @phpstan-ignore-next-line
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
