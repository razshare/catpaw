<?php
namespace CatPaw\Go\Implementations\Go;

use CatPaw\Core\Attributes\Provider;

use function CatPaw\Core\error;
use CatPaw\Core\File;

use function CatPaw\Core\ok;
use CatPaw\Core\ReflectionTypeManager;
use CatPaw\Core\Unsafe;
use CatPaw\Go\Interfaces\GoInterface;
use Error;
use FFI;
use FFI\CData;
use FFI\ParserException;
use Phar;
use ReflectionClass;
use Throwable;

#[Provider]
class SimpleGo implements GoInterface {
    /**
     * Load a Go library.
     * @template T
     * @param  class-string<T> $interface Contract interface, as in - what functions the go library exposes.
     * @param  string          $fileName  Main go file.
     * @return Unsafe<T>
     */
    public static function load(string $interface, string $fileName):Unsafe {
        $isPharFileName = str_starts_with($fileName, 'phar://');

        $strippedFileName = preg_replace('/\.so$/', '', $fileName);

        if ($isPharFileName) {
            $localHeaderFileName = "$strippedFileName.static.h";
            $phar                = Phar::running();
            $headerFileName      = './'.basename('.'.str_replace($phar, '', $localHeaderFileName));
            if (!File::exists($headerFileName)) {
                File::copy($localHeaderFileName, $headerFileName)->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
        } else {
            $headerFileName = "$strippedFileName.static.h";
        }

        $headerFile = File::open($headerFileName)->unwrap($error);
        if ($error) {
            $headerFile = File::open("$strippedFileName.h")->unwrap($error);
            if ($error) {
                return error($error);
            }
        }
        $cdefComplex = $headerFile->readAll()->unwrap($error);


        if ($error) {
            return error($error);
        }

        if (null === ($cdef = preg_replace('/^\s*typedef\s+(float|double)\s+_Complex.*/m', '', $cdefComplex))) {
            return error("Unknown error while trying to clear `_Complex` definitions in the header file {$headerFile->fileName}.");
        }

        $externalFileName = '';
        try {
            if ($isPharFileName) {
                $phar             = Phar::running();
                $externalFileName = './'.basename('.'.str_replace($phar, '', $fileName));

                if (!File::exists($externalFileName)) {
                    File::copy($fileName, $externalFileName)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                }

                $lib = FFI::cdef($cdef, $externalFileName);
            } else {
                $lib = FFI::cdef($cdef, $fileName);
            }
        } catch(Throwable $error) {
            return error($error);
        }

        try {
            $methods = self::signContract($interface, $lib);
        } catch(Throwable $error) {
            return error($error);
        }

        /** @var T */
        return ok(new class($methods) {
            /**
             * @param  array<callable(mixed...):mixed[]> $methods
             * @return void
             */
            public function __construct(private array $methods) {
            }

            /**
             * @param  string  $name
             * @param  mixed   $arguments
             * @return mixed[]
             */
            public function __call(string $name, mixed $arguments) {
                return $this->methods[$name](...$arguments);
            }
        });
    }





    /**
     * Parse the given `$interface` and create a _contract_ (let's call it so), which is a set of methods
     * you can use to more easily call your Go functions.
     *
     * Technically this is not necessary, you can invoke Go functions directly through `$goffi->lib->myFunction()`,
     * but it's not as simple as it looks, because some Go primitives require creating some specific structures and don't map directly to Php primitives.\
     * For example Go strings are slices, and they are defined as
     * ```c
     * typedef struct { const char *p; ptrdiff_t n; } _GoString_;
     * ```
     * Php's FFI api will not convert php `string`s into `_GoString_`s (understandably so), which means you would need to do it by hand, which is not the best experience.
     *
     * This method will create the necessary _contract methods_ that will automate your Go calls, converting php strings and other primitives to their correct Go counterparts.
     * @param  string                                   $interface
     * @param  FFI                                      $lib
     * @throws Error
     * @return array<callable(mixed ...$args): mixed[]>
     */
    private static function signContract(string $interface, FFI $lib):array {
        $reflectionClass = new ReflectionClass($interface);
        $methods         = [];
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $methodName           = $reflectionMethod->getName();
            $reflectionParameters = $reflectionMethod->getParameters();
            $methodReturnType     = $reflectionMethod->getReturnType();
            if (!$methodReturnType) {
                throw new Error("The Go contract method `$methodName` must specify a return type.");
            }



            $resolvers   = [];
            $paramsCount = 0;

            foreach ($reflectionParameters as $reflectionParameter) {
                $parameterName = $reflectionParameter->getName();
                if (!$type = ReflectionTypeManager::unwrap($reflectionParameter)) {
                    throw new Error("The Go contract method `$methodName` defines parameter `$parameterName` without a type, which is not allowed. Please make sure that parameter `$parameterName` specifies a type.");
                }
                $paramsCount++;
                $resolvers[] = match ($type->getName()) {
                    // 'string' => fn (string $phpString) => self::goString($lib, $phpString),
                    'string' => fn (string $value) => ok($value),
                    'int'    => fn (int $value) => ok($value),
                    'float'  => fn (float $value) => ok($value),
                    'bool'   => fn (bool $value) => ok($value),
                    default  => fn (mixed $value) => ok($value),
                };
            }

            $returnType     = ReflectionTypeManager::unwrap($methodReturnType);
            $returnTypeName = $returnType->getName();

            $methods[$methodName] = function(...$args) use (
                $resolvers,
                $paramsCount,
                $methodName,
                $interface,
                $lib,
                $returnTypeName,
            ) {
                // Check that the correct number of parameters are being received.
                // In the future I should check for optional/nullable parameters and decide what to do with those.
                // For now all defined parameters will be required for simplicity sake.
                $argsCount = count($args);
                if ($paramsCount !== $argsCount) {
                    throw new Error("The Go contract method `$methodName` in interface `$interface` is expecting $paramsCount parameters, but $argsCount have been received. Please make sure the correct number of parameters are being passed.");
                }

                $resolvedArgs = [];
                foreach ($args as $key => $arg) {
                    $resolver = $resolvers[$key];
                    /** @var Unsafe<mixed> $result */
                    $result = $resolver($arg);
                    $value  = $result->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $resolvedArgs[] = $value;
                }
                $result = $lib->$methodName(...$resolvedArgs);

                if ('string' === $returnTypeName) {
                    $converted = FFI::string($result);
                    FFI::free($result);
                    return $converted;
                }

                return $result;
            };
        }
        return $methods;
    }


    /**
     * Create a Go string from a Php string.
     * @param  FFI             $lib
     * @param  string          $phpString
     * @throws ParserException
     * @return CData|null      the Go string.
     */
    private static function goString(FFI $lib, string $phpString) {
        $struct = $lib->new('_GoString_');
        $count  = strlen($phpString);
        // @phpstan-ignore-next-line
        $struct->p = $lib->new('char['.$count.']', 0);

        FFI::memcpy($struct->p, $phpString, $count);
        // @phpstan-ignore-next-line
        $struct->n = $count;

        return $struct;
    }
}
