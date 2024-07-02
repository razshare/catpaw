<?php
namespace CatPaw\Core;

use ReflectionFunction;

class Command {
    /**
     * Create a command using a signature and a callback function.
     * @param  string                $signature Command signature, the options required for this command to trigger.
     * @param  callable(mixed):mixed $function  Function to execute when command is issued.
     * @return Unsafe<bool>          `true` if the command was executed, `false` otherwise.
     */
    public static function create(string $signature, callable $function):Unsafe {
        $reflectionFunction = new ReflectionFunction($function);
        $options            = CommandParser::options($signature, $reflectionFunction)->unwrap($error);

        if ($error) {
            return error($error);
        }

        if (count($options) === 0) {
            $actualItems   = CommandParser::parseSignature($signature);
            $expectedItems = CommandParser::parseFunction($reflectionFunction)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $actualItemsCount   = count($actualItems);
            $expectedItemsCount = count($expectedItems);
            if ($actualItemsCount !== $expectedItemsCount) {
                return ok(false);
            }
            foreach ($expectedItems as $expectedItem) {
                if (!str_ends_with($expectedItem, '::')) {
                    return ok(false);
                }
            }
        }

        $arguments = [];

        static $undefined = function(WrappedType $type, bool $notFound, string $value) {
            if ($notFound) {
                if ($type->allowsDefaultValue()) {
                    return $type->getDefaultValue();
                }

                if ($type->allowsFalse()) {
                    return false;
                }

                if ($type->allowsNullValue()) {
                    return null;
                }

                return NONE;
            }

            if ('' === $value) {
                if ($type->allowsBoolean()) {
                    return true;
                }

                if ($type->allowsTrue()) {
                    return true;
                }

                return NONE;
            }
            return NONE;
        };

        static $integer = fn (WrappedType $type, bool $notFound, string $value):mixed 
        => match ($result = $undefined($type, $notFound, $value)) {
            NONE    => (integer)$value,
            default => $result,
        };

        static $float = fn (WrappedType $type, bool $notFound, string $value):mixed 
        => match ($result = $undefined($type, $notFound, $value)) {
            NONE    => (float)$value,
            default => $result,
        };

        static $double = fn (WrappedType $type, bool $notFound, string $value):mixed 
        => match ($result = $undefined($type, $notFound, $value)) {
            NONE    => (double)$value,
            default => $result,
        };

        static $bool = fn (WrappedType $type, bool $notFound, string $value):mixed 
        => match ($result = $undefined($type, $notFound, $value)) {
            NONE    => (bool)$value,
            default => $result,
        };

        static $string = fn (WrappedType $type, bool $notFound, string $value):mixed 
        => match ($result = $undefined($type, $notFound, $value)) {
            NONE    => (string)$value,
            default => $result,
        };

        foreach ($reflectionFunction->getParameters() as $reflectionParameter) {
            $key       = KebabCase::fromAny($reflectionParameter->getName());
            $notFound  = !isset($options[$key]);
            $value     = $options[$key] ?? '';
            $type      = ReflectionTypeManager::wrap($reflectionParameter);
            $className = $type->getClassName();
            $result    = match ($className) {
                'int'    => $integer($type, $notFound, $value),
                'float'  => $float($type, $notFound, $value),
                'double' => $double($type, $notFound, $value),
                'bool'   => $bool($type, $notFound, $value),
                default  => $string($type, $notFound, $value),
            };
            $arguments[] = $result;
        }

        foreach ($arguments as $argument) {
            if (NONE === $argument) {
                return ok(false);
            }
        }

        return anyError(function() use ($function, $arguments) {
            yield $function(...$arguments);
            return ok(true);
        });
    }
}