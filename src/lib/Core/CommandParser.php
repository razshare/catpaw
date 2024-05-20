<?php
namespace CatPaw\Core;

use ReflectionFunction;

class CommandParser {
    /**
     * Parse a signature.
     * @param  string        $signature
     * @return array<string> A list of strings containing the names of 
     *                       all options present in the signature.
     */
    public static function parseSignature(string $signature):array {
        $definitions = [];
        $count       = 0;
        do {
            if (preg_match('/\s*--([\w-]+)\s*/', $signature, $match)) {
                $signature     = preg_replace('/\s*--[\w-]+\s*/', '', $signature, 1, $count);
                $definitions[] = $match[1];
            } else {
                break;
            }
        } while ($count > 0);
    
        $count = 0;
        do {
            if (preg_match('/\s*-(\w)\s*/', $signature, $match)) {
                $signature     = preg_replace('/\s*-\w\s*/', '', $signature, 1, $count);
                $definitions[] = $match[1];
            } else {
                break;
            }
        } while ($count > 0);

        return $definitions;
    }

    /**
     * Given a reflection function, parse its parameters and
     * get an array of options which you can pass into `getopt()`.
     * @param  ReflectionFunction    $reflectionOptions
     * @return Unsafe<array<string>> A list of options compatible with `getopt()`.
     */
    public static function parseFunction(ReflectionFunction $reflectionOptions):Unsafe {
        /** @var array<string> */
        $options = [];
        foreach ($reflectionOptions->getParameters() as $reflectionParameter) {
            $type = ReflectionTypeManager::wrap($reflectionParameter);
            // $className    = $type->getClassName();
            $propertyName = $reflectionParameter->getName();
            
            if ($type->allowsNullValue() || ($type->allowsDefaultValue() && $type->getDefaultValue() === null)) {
                return error("Command option {$propertyName} are not allowed to be `nullable` or have a `null` default value. Use proper default values instead.");
            }
    
            if (
                $type->allowsFalse()
                || $type->allowsBoolean()
                || $type->allowsDefaultValue()
            ) {
                $options[] = KebabCase::fromAny($propertyName).'::';
            } else {
                $options[] = KebabCase::fromAny($propertyName).':';
            }
        }
        return ok($options);
    }

    /**
     * Parse both a signature and a reflection function and return
     * an array of options which you can pass into `getopt()`.\
     * If the signature defines an option which the reflection 
     * function omits from its properties,
     * then that option is interpreted as an option with no value.
     * @see https://www.php.net/manual/en/function.getopt.php
     * @param  string                       $signature
     * @param  ReflectionFunction           $reflectionFunction
     * @return Unsafe<array<string,string>> A list of options compatible with `getopt()`.
     */
    public static function options(string $signature, ReflectionFunction $reflectionFunction):Unsafe {
        $actualItems   = CommandParser::parseSignature($signature);
        $expectedItems = CommandParser::parseFunction($reflectionFunction)->unwrap($error);
        if ($error) {
            return error($error);
        }
    
        $actualItemsCount   = count($actualItems);
        $expectedItemsCount = count($expectedItems);
    
        if ($expectedItemsCount > $actualItemsCount) {
            return error("Command function must not declare more properties than available options in signature `$signature`.");
        }
    
        foreach ($actualItems as $actual) {
            $isExpected = in_array($actual, $expectedItems)
                         || in_array("$actual:", $expectedItems)
                         || in_array("$actual::", $expectedItems);
    
            if (!$isExpected) {
                $expectedItems[] = "$actual::";
            }
        }
    
        /** @var array<string,string> */
        $options = getopt('', $expectedItems)?:[];
        if (count($options) === 0) {
            // @phpstan-ignore-next-line
            return ok([]);
        }
        
        foreach ($options as &$value) {
            // @phpstan-ignore-next-line
            if (false === $value) {
                $value = '';
            }
        }

        // @phpstan-ignore-next-line
        return ok($options);
    }
}