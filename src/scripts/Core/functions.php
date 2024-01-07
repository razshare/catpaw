<?php
namespace CatPaw;

use function Amp\async;

use Amp\ByteStream\ReadableIterableStream;
use Amp\ByteStream\WritableIterableStream;
use Amp\Future;
use Amp\Process\Process;
use Error;
use Exception;
use Phar;
use React\Promise\Promise;
use React\Stream\ReadableResourceStream;
use React\Stream\ThroughStream;
use React\Stream\WritableResourceStream;
use React\Stream\WritableStreamInterface;
use RecursiveArrayIterator;


use RecursiveIteratorIterator;

use Throwable;

/**
 * Get current time in milliseconds.
 * @return float
 */
function milliseconds():float {
    return floor(microtime(true) * 1000);
}

/**
 * Check if an array is associative.
 * @param  array $arr
 * @return bool  true if the array is associative, false otherwise.
 */
function isAssoc(array $arr) {
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
function isPhar() {
    return strlen(Phar::running()) > 0 ? true : false;
}

/**
 * Request an input from the terminal without feeding back to the display whatever it's been typed.
 * @param  string         $prompt message to display along with the input request.
 * @return Unsafe<string>
 */
function readLineSilent(string $prompt):Unsafe {
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
function flatten(array $array, bool $completely = false):array {
    if ($completely) {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($array)), false);
    }

    return array_merge(...array_values($array));
}

/**
 * Get the stdout as a stream.
 */
function out():WritableResourceStream {
    return new WritableResourceStream(STDOUT);
}

/**
 * Get the stdin as a stream.
 */
function in():ReadableResourceStream {
    return new ReadableResourceStream(STDIN);
}

/**
 * @template T
 * @param  T         $value
 * @return Unsafe<T>
 */
function ok(mixed $value = null):Unsafe {
    return new Unsafe($value, false);
}

/**
 * @template T
 * @param  string|Error $message
 * @return Unsafe<T>
 */
function error(string|Error $message):Unsafe {
    if (is_string($message)) {
        return new Unsafe(null, new Error($message));
    }

    return new Unsafe(null, $message);
}

/**
 * Execute a command.
 * @param  string                        $command   instruction to run.
 * @param  false|WritableStreamInterface $writer    send the output of the process to this stream.
 * @param  false|Signal                  $signal    when this signal is triggered the process is killed.
 * @param  bool                          $autoClose automatically close the `$writer` stream when the process is over.
 * @return Future<Unsafe<void>>
 */
function execute(
    string $command,
    false|WritableStreamInterface $writer = false,
    false|Signal $signal = false,
    bool $autoClose = true,
):Future {
    return async(static function($ok) use ($command, $writer, $signal, $autoClose) {
        try {
            $process = Process::start($command);
        } catch(Throwable $e) {
            if ($writer && $autoClose) {
                $writer->close();
            }
            return error($e->getMessage());
        }

        if ($signal) {
            $signal->listen(static function(int $code) use ($process) {
                if (!$process->isRunning()) {
                    return;
                }
                $process->signal($code);
            });
        }

        

        // if ($writer) {
        //     $process->stdout->on('data', static function($chunk) use ($writer) {
        //         $writer->write($chunk);
        //     });
        // }
        
        // $process->stdout->on('end', static function() use ($ok, $autoClose, $writer) {
        //     if ($autoClose) {
        //         $writer->close();
        //     }
        //     $ok(ok());
        // });
        
        // $process->stdout->on('error', static function(Exception $e) use ($ok, $autoClose, $writer) {
        //     if ($autoClose) {
        //         $writer->close();
        //     }
        //     $ok(error($e->getMessage()));
        // });
        
        // $process->stdout->on('close', static function() use ($ok, $autoClose, $writer) {
        //     if ($autoClose) {
        //         $writer->close();
        //     }
        //     $ok(ok());
        // });
    });
}

/**
 * Execute a command and return its output.
 * @param  string                  $command command to run
 * @return Promise<Unsafe<string>>
 */
function get(string $command):Promise {
    $through = new ThroughStream();
    execute($command, $through);
    $output = '';

    return new Promise(static function($ok) use ($through, &$output) {
        $through->on('data', static function($chunk) use ($through, &$output) {
            $output .= $chunk;
        });
        
        $through->on('end', static function() use ($ok, &$output) {
            $ok(ok($output));
        });
        
        $through->on('error', static function(Exception $e) use ($ok) {
            $ok(error($e->getMessage()));
        });
        
        $through->on('close', static function() use ($ok, &$output) {
            $ok(ok($output));
        });
    });
}


/**
 * If any unsafe error is found among the parameters, return it.
 * @return Unsafe<void>
 */
function anyError(Unsafe ...$unsafes):Unsafe {
    foreach ($unsafes as $unsafe) {
        if ($unsafe->error) {
            return error($unsafe->error);
        }
    }
    return ok();
}

/**
 * Return two new streams, a readable stream and a writable one which will be writing to the first stream.
 * 
 * The writer stream will automatically be disposed of when the readable stream is disposed of.
 * @param  int                                                      $bufferSize
 * @return array{0:ReadableIterableStream,1:WritableIterableStream}
 */
function duplex(int $bufferSize = 8192):array {
    $writer = new WritableIterableStream($bufferSize);
    $reader = new ReadableIterableStream($writer);
    return [$reader, $writer];
}