<?php

namespace CatPaw\Core;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\ReadableResourceStream;
use Amp\ByteStream\WritableIterableStream;
use Amp\ByteStream\WritableResourceStream;
use Amp\ByteStream\WritableStream;
use Amp\DeferredFuture;
use Amp\Future;
use Amp\Process\Process;
use Error;
use Generator;
use Phar;
use Psr\Log\LoggerInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Throwable;
use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\ByteStream\getStdin;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\pipe;


/**
 * Get current time in milliseconds.
 * @return float
 */
function milliseconds(): float {
    return floor(microtime(true) * 1000);
}

/**
 * Check if an array is associative.
 * @param array $arr
 * @return bool  true if the array is associative, false otherwise.
 */
function isAssoc(array $arr): bool {
    if([] === $arr) {
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
 * @param string $prompt message to display along with the input request.
 * @return Unsafe<string>
 */
function readLineSilent(string $prompt): Unsafe {
    $command = "/usr/bin/env bash -c 'echo OK'";
    if(rtrim(shell_exec($command)) !== 'OK') {
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
 * @param array $array
 * @param bool  $completely if true, flatten the array completely
 * @return array
 */
function flatten(array $array, bool $completely = false): array {
    if($completely) {
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
 * @param T $value
 * @return Unsafe<T>
 */
function ok(mixed $value = null): Unsafe {
    return new Unsafe($value, false);
}

/**
 * @param string|Error $message
 * @return Unsafe<void>
 */
function error(string|Error $message): Unsafe {
    if(is_string($message)) {
        return new Unsafe(null, new Error($message));
    }

    return new Unsafe(null, $message);
}

/**
 * Execute a command.
 * @param string               $command instruction to run.
 * @param false|WritableStream $writer send the output of the process to this stream.
 * @param false|Signal         $signal when this signal is triggered the process is killed.
 * @return Future<Unsafe<int>>
 */
function execute(
    string               $command,
    false|WritableStream $writer = false,
    false|Signal         $signal = false,
): Future {
    return async(static function() use ($command, $writer, $signal) {
        try {
            /** @var Unsafe<LoggerInterface> $loggerAttempt */
            $loggerAttempt = Container::create(LoggerInterface::class);
            if($loggerAttempt->error) {
                return error($loggerAttempt->error);
            }
            $logger = $loggerAttempt->value;
            $process = Process::start($command);
            pipe($process->getStdout(), $writer);
            pipe($process->getStderr(), $writer);
            $code = $process->join();
        } catch(Throwable $e) {
            return error($e->getMessage());
        }

        if($signal) {
            $signal->listen(static function(int $code) use ($process, $writer, $logger) {
                if(!$process->isRunning()) {
                    return ok();
                }

                try {
                    $process->signal($code);
                    return ok();
                } catch(Throwable $e) {
                    $logger->error($e->getMessage());
                    return error($e);
                }
            });
        }

        return ok($code);
    });
}

/**
 * Execute a command and return its output.
 * @param string $command command to run
 * @return Future<Unsafe<string>>
 */
function get(string $command): Future {
    return async(static function() use ($command) {
        [$reader, $writer] = duplex();
        if($error = execute($command, $writer)->await()->error) {
            return error($error);
        }
        return ok(buffer($reader));
    });
}

/**
 * Invoke a generator function and immediately return any `Error`
 * or `Unsafe` containing an error generated as `Unsafe`.
 * @param callable():Generator<Unsafe|Error> $function
 * @return Unsafe<void>
 */
function anyError(callable $function): Unsafe {
    /** @var Generator<Unsafe<mixed>> $result */
    $result = $function();

    if(!($result instanceof Generator)) {
        if($result instanceof Unsafe) {
            return $result;
        }
        return ok($result);
    }

    for($result->rewind(); $result->valid(); $result->next()) {
        /** @var Unsafe<Error|Unsafe> $value */
        $value = $result->current();
        if($value instanceof Error) {
            return error($value);
        } elseif($value instanceof Unsafe && $value->error) {
            return error($value->error);
        }
    }
    return ok();
}

/**
 * Return two new streams, a readable stream and a writable one which will be writing to the first stream.
 *
 * The writer stream will automatically be disposed of when the readable stream is disposed of.
 * @param int $bufferSize
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
 * Get an environment variable.
 * @template T
 * @param string $name name of the variable.
 * @return T      value of the variable.
 */
function env(string $name): mixed {
    return $_ENV[$name];
}


/**
 * Stop the program with an error.
 * @param string|Error $error
 * @return never
 */
function stop(string|Error $error) {
    if(is_string($error)) {
        $error = new Error($error);
    }
    Bootstrap::kill($error->getMessage());
}
