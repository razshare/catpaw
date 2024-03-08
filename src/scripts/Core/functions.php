<?php

namespace CatPaw\Core;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\ByteStream\getStdin;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\pipe;
use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\WritableIterableStream;
use Amp\ByteStream\WritableResourceStream;
use Amp\ByteStream\WritableStream;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Process\Process;
use CatPaw\Core\Services\EnvironmentService;
use Error;
use FFI;
use Generator;
use Phar;
use Psr\Log\LoggerInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Throwable;


/**
 * Get current time in milliseconds.
 * @return float
 */
function milliseconds(): float {
    return floor(microtime(true) * 1000);
}

/**
 * Check if an array is associative.
 * @param  array $arr
 * @return bool  true if the array is associative, false otherwise.
 */
function isAssoc(array $arr): bool {
    if ([] === $arr) {
        return false;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
}


/**
 * Generate a universally unique identifier
 *
 * *Caution*: this function does not generate cryptographically secure values, and must not be used for cryptographic purposes, or purposes that require returned values to be unguessable.
 * @return string the uuid
 */
function uuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

/**
 * Check if the current application is running inside a .phar archive or not.
 * @return bool
 */
function isPhar(): bool {
    return strlen(Phar::running()) > 0 ? true : false;
}

/**
 * Request an input from the terminal without feeding back to the display whatever it's been typed.
 * @param  string         $prompt message to display along with the input request.
 * @return Unsafe<string>
 */
function readLineSilent(string $prompt): Unsafe {
    $command = "/usr/bin/env bash -c 'echo OK'";
    if (rtrim(shell_exec($command)) !== 'OK') {
        return error("Can't invoke bash");
    }
    $command = "/usr/bin/env bash -c 'read -s -p \""
        .addslashes($prompt)
        ."\" hidden_value && echo \$hidden_value'";
    $hiddenValue = rtrim(shell_exec($command));
    echo "\n";
    return ok($hiddenValue);
}


/**
 * @param  array $array
 * @param  bool  $completely if true, flatten the array completely
 * @return array
 */
function flatten(array $array, bool $completely = false): array {
    if ($completely) {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($array)), false);
    }

    return array_merge(...array_values($array));
}

/**
 * Get the stdout as a stream.
 */
function out(): WritableResourceStream {
    return getStdout();
}

/**
 * Get the stdin as a stream.
 */
function in(): ReadableResourceStream {
    return getStdin();
}

/**
 * @template T
 * @param  T         $value
 * @return Unsafe<T>
 */
function ok(mixed $value = true): Unsafe {
    return new Unsafe($value, false);
}

/**
 * @param  string|Error $message
 * @return Unsafe<void>
 */
function error(string|Error $message): Unsafe {
    if (is_string($message)) {
        return new Unsafe(null, new Error($message));
    }

    return new Unsafe(null, $message);
}


/**
 * Execute a command.
 * @param  string              $command       Command to run.
 * @param  bool|WritableStream $output        Send the output of the process to this stream.
 * @param  bool|string         $workDirectory Work directory of the command.
 * @param  bool|Signal         $kill          When this signal is triggered the process is killed.
 * @return Future<Unsafe<int>>
 */
function execute(
    string $command,
    false|WritableStream $output = false,
    false|string $workDirectory = false,
    false|Signal $kill = false,
): Future {
    return async(static function() use ($command, $output, $kill, $workDirectory) {
        try {
            $logger = Container::create(LoggerInterface::class)->try($error);
            if ($error) {
                return error($error);
            }
            $process = Process::start($command, $workDirectory?:null);
            pipe($process->getStdout(), $output);
            pipe($process->getStderr(), $output);
            $code = $process->join();
        } catch(Throwable $error) {
            return error($error);
        }

        if ($kill) {
            $kill->listen(static function(int $code) use ($process, $output, $logger) {
                if (!$process->isRunning()) {
                    return ok();
                }

                try {
                    $process->signal($code);
                    return ok();
                } catch(Throwable $error) {
                    $logger->error($error);
                    return error($error);
                }
            });
        }

        return ok($code);
    });
}

/**
 * Execute a command and return its output.
 * @param  string                 $command command to run
 * @return Future<Unsafe<string>>
 */
function get(string $command): Future {
    return async(static function() use ($command) {
        [$reader, $writer] = duplex();
        execute($command, $writer)->await()->try($error);
        if ($error) {
            return error($error);
        }
        return ok(buffer($reader));
    });
}

/**
 * Invoke a generator function and immediately return any `Error`
 * or `Unsafe` that contains an error.\
 * In both cases the result is always an `Unsafe<T>` object.
 *
 * - If you generate an `Unsafe<T>` the error within the object is transferred to a new `Unsafe<T>` for the sake of consistency.
 * - If you generate an `Error` instead, then the `Error` is wrapped in `Unsafe<T>`.
 *
 * The generator is consumed and if no error is detected then the function produces the returned value of the generator.
 *
 * ## Example
 * ```php
 * $content = anyError(function(){
 *  $file = File::open('file.txt')->try($error)
 *  or yield $error;
 *
 *  $content = $file->readAll()->await()->try($error)
 *  or yield $error;
 *
 *  return $content;
 * });
 * ```
 * @template T
 * @param  callable():Generator<Unsafe|Error|T> $function
 * @return Unsafe<T>
 */
function anyError(callable $function): Unsafe {
    /** @var Generator<Unsafe<mixed>> $result */
    $result = $function();

    if (!($result instanceof Generator)) {
        if ($result instanceof Unsafe) {
            return $result;
        }
        return ok($result);
    }

    for ($result->rewind(); $result->valid(); $result->next()) {
        /** @var Unsafe<Error|Unsafe> $value */
        $value = $result->current();
        if ($value instanceof Error) {
            return error($value);
        } elseif ($value instanceof Unsafe && $value->error) {
            return error($value->error);
        }
    }

    try {
        $return = $result->getReturn() ?? true;

        if ($return instanceof Error) {
            return error($return);
        } else if ($return instanceof Unsafe) {
            return $result;
        }

        return ok($return);
    } catch (Throwable $error) {
        return error($error);
    }
}

/**
 * Return two new streams, a readable stream and a writable one which will be writing to the first stream.
 *
 * The writer stream will automatically be disposed of when the readable stream is disposed of.
 * @param  int                                                      $bufferSize
 * @return array{0:ReadableIterableStream,1:WritableIterableStream}
 */
function duplex(int $bufferSize = 8192): array {
    $writer = new WritableIterableStream($bufferSize);
    $reader = new ReadableIterableStream($writer);
    return [$reader, $writer];
}

/**
 * Resolve on the next event loop tick.
 * @return Future<void>
 */
function tick(): Future {
    return (new DeferredFuture)->getFuture()->complete();
}

function deferred(): DeferredFuture {
    return new DeferredFuture;
}


/**
 * Find an environment variable by name.
 *
 * ## Example
 * ```php
 * $service->findByName("server")['www'];
 * // or better even
 * $service->$findByName("server.www");
 * ```
 * @template T
 * @param  string $query name of the variable or a query in the form of `"key.subkey"`.
 * @return T      value of the variable.
 */
function env(string $query): mixed {
    /** @var false|EnvironmentService */
    static $env = false;

    if (!$env) {
        $env = Container::create(EnvironmentService::class)->try($error);
        if ($error) {
            Bootstrap::kill("Couldn't load environment service.\n$error", CommandStatus::NO_DATA_AVAILABLE);
        }
    }

    return $env->get($query);
}


/**
 * Stop the program with an error.
 * @param  string|Error $error
 * @return never
 */
function stop(string|Error $error) {
    if (is_string($error)) {
        $error = new Error($error);
    }
    Bootstrap::kill((string)$error);
}

/**
 * Given a `$path`, create a file name.
 * @param  string ...$path
 * @return string
 */
function asFileName(string ...$path):string {
    $parts = [];
    $count = count($path);
    for ($index = 0; $index < $count; $index++) {
        $pathName = $path[$index];
        if ($index < $count - 1 && !str_ends_with($pathName, '/')) {
            $pathName = "$pathName/";
        }
        $parts[] = $pathName;
    }
    return realpath(join($parts))?:'';
}

/**
 * Given a `$path`, create a file name.
 * @param  string ...$path
 * @return string
 */
function asPharFileName(string ...$path):string {
    if (isPhar()) {
        $phar  = Phar::running();
        $path  = [$phar, ...$path];
        $parts = [];
        $count = count($path);
        for ($index = 0; $index < $count; $index++) {
            $pathName = $path[$index];

            if ($index < $count - 1 && !str_ends_with($pathName, '/')) {
                $pathName = "$pathName/";
            }
            $parts[] = $pathName;
        }
        $result = join($parts);
        $result = preg_replace('#/\\./#', '/', $result);
        return $result;
    } else {
        return asFileName(...$path);
    }
}

/**
 * Create an _FFI_ wrapper for a _Go_ shared object.
 *
 * # WARNING!
 * > This is still experimental and in development.\
 * > **DO NOT USE IN PRODUCTION!**
 *
 * # Example
 *
 * Given the following _Go_ program
 *
 * ```
 * // goffi.go
 * package main
 * import "C"
 * func main() {}
 *
 * //export DoubleIt
 * func DoubleIt(x int) int {
 *  return x * 2
 * }
 * ```
 *
 * 1. Compile it with `go build -o libgoffi.so -buildmode=c-shared goffi.go`
 * 2. Resolve the c directives in your header file with `cpp -P ./libgoffi.h ./libgoffi.static.h`
 *
 * Afterwards you can use `goffi()` to call your _Go_ code from _Php_.
 *
 * ```php
 * <?php
 * // main.php
 * use function CatPaw\Core\goffi;
 *
 *  interface Goffi {
 *      function DoubleIt(int $value):Unsafe;
 *  }
 *
 * function main(){
 *
 *  $lib = goffi(Goffi::class, './libgoffi.so')->try($error);
 *  if($error){
 *      return error($error);
 *  }
 *
 *  $doubled = $lib->DoubleIt(3);
 *  echo "doubled: $doubled\n";
 * }
 * ```
 * @template T
 * @param  class-string<T>         $interface Interface of the shared object.\
 *                                            This will give you intellisense.\
 *                                            Methods of this interface must all return `Unsafe`.\
 *                                            Remember you cal always use php-poc to specify `Unsafe<T>` instead of just `Unsafe`, in order to get proper intellisense.
 * @param  string                  $fileName  Name of the shared object file, for example `lib.so`.\
 *                                            The shared object's equivalent C definition file must be located in the same directory as `$fileName` and have the `.h` or `.static.h` extension.\
 *                                            This header file must not contain any C preprocessor directives.\
 *                                            You can resolve C preprocessor directives in a header file by running `cpp -P ./lib.h ./lib.static.h`.\
 *                                            You can read more about this [here](https://www.php.net/manual/en/ffi.cdef.php).
 * @return Unsafe<GoffiContract&T>
 */
function goffi(string $interface, string $fileName) {
    return GoffiContract::create($interface, $fileName);
}
