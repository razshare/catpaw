<?php
namespace CatPaw;

use function Amp\ByteStream\buffer;

use function Amp\call;
use Amp\Deferred;

use function Amp\delay;
use function Amp\File\createDirectoryRecursively;
use function Amp\File\deleteDirectory;
use function Amp\File\deleteFile;
use function Amp\File\exists;
use function Amp\File\getStatus;
use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\listFiles;
use function Amp\File\read;

use function Amp\File\write;
use Amp\Process\Process;
use Amp\Promise;
use CatPaw\Utilities\AsciiTable;
use CatPaw\Utilities\Stream;
use Closure;
use Error;
use InvalidArgumentException;
use Phar;

/**
 * Get current time in milliseconds.
 * @return float
 */
function milliseconds():float {
    return floor(microtime(true) * 1000);
}

/**
 * List all files (not directories) inside a directory.
 * Dot entries are not included in the resulting array (i.e. "." and "..").
 * @param  string                 $path
 * @param  array|false            $ignore
 * @return Promise<array<string>>
 */
function listFilesRecursively(string $path, array|false $ignore = false):Promise {
    if (!\str_ends_with($path, '/')) {
        $path .= '/';
    }
    return call(function() use ($path, $ignore) {
        $filenames = yield listFiles($path);
        $files     = [];
        foreach ($filenames as $filename) {
            if ($ignore && in_array($filename, $ignore)) {
                continue;
            }

            $filename = "$path$filename";
            $isDir    = yield isDirectory($filename);
            if ($isDir) {
                foreach (yield listFilesRecursively($filename) as $subItem) {
                    $files[] = $subItem;
                }
                continue;
            }

            $files[] = $filename;
        }
        return $files;
    });
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
 * Convert a resource into a stream.
 * @param  mixed  $resource
 * @param  ?int   $chunkSize
 * @return Stream
 */
function stream(
    mixed $resource,
    ?int $chunkSize = null,
):Stream {
    return Stream::of($resource, $chunkSize);
}

/**
 * Delete a file or directory recursively.
 * @param  string                   $path   name of the directory.present.
 * @param  array|false              $ignore a map of file/directory names to ignore.
 * @throws InvalidArgumentException if the specified directory name is not actually a directory.
 * @return Promise<void>
 */
function deleteDirectoryRecursively(string $path, array|false $ignore = false):Promise {
    return call(function() use ($path, $ignore) {
        if (!yield isDirectory($path)) {
            throw new InvalidArgumentException("\"$path\" is not a valid directory.");
        }

        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        
        $files = yield listFiles($path);
        
        foreach ($files as $filename) {
            if (false !== $ignore && in_array($filename, $ignore)) {
                continue;
            }

            $target = "$path$filename";

            if (yield isFile($target)) {
                yield deleteFile($target);
                continue;
            }

            yield deleteDirectoryRecursively($target);
        }

        yield deleteDirectory($path);
    });
}

/**
 * Copy a file.
 * @param  string        $from
 * @param  string        $to
 * @return Promise<void>
 */
function copyFile(string $from, string $to):Promise {
    return call(function() use ($from, $to) {
        yield write($to, yield read($from));
        // $source      = stream(fopen($from, 'r'));
        // $destination = stream(fopen($to, 'w+'));
        // while ($chunk = yield $source->read()) {
        //     yield $destination->write($chunk);
        // }
        // yield $destination->close();
        // yield $source->close();
    });
}

/**
 * Copy a file or directory recursively.
 * @param  string      $from
 * @param  string      $to
 * @param  array|false $ignore a map of file/directory names to ignore.
 * @return Promise
 */
function copyDirectoryRecursively(string $from, string $to, array|false $ignore = false):Promise {
    return call(function() use ($from, $to, $ignore) {
        if (!str_ends_with($from, '/')) {
            $from .= '/';
        }

        if (!str_ends_with($to, '/')) {
            $to .= '/';
        }

        if (!yield exists($to)) {
            yield createDirectoryRecursively($to);
        }

        foreach (yield listFiles($from) as $filename) {
            if (false !== $ignore && in_array($filename, $ignore)) {
                continue;
            }

            $source      = "$from$filename";
            $destination = "$to$filename";

            if (yield isFile($source)) {
                yield copyFile($source, $destination);
                continue;
            }

            yield copyDirectoryRecursively($source, $destination);
        }
    });
}

/**
 * Get the filenames within a directory recursively.
 * @param  string                              $path   startup directory.
 * @param  array|false                         $ignore a map of file/directory names to ignore.
 * @return Promise<array<array<string,mixed>>>
 */
function listFilesInfoRecursively(string $path, array|false $ignore = false):Promise {
    return call(function() use ($path, $ignore) {
        $list = [];
        if (yield isDirectory($path)) {
            if (!str_ends_with($path, '/')) {
                $path .= '/';
            }
            foreach (yield listFiles($path) as $i => $filename) {
                if (false !== $ignore && \in_array($filename, $ignore)) {
                    continue;
                }              
                $list = [ ...$list, ...yield listFilesInfoRecursively("$path$filename")];
            }
        } else {
            $list = [
                ...$list,
                yield getStatus($path)
            ];
        }

        return $list;
    });
}

/**
 * Create a process, run it, wait for it to end and get its status code.
 * @return Promise<string> the output data of the process.
 */
function execute(string $command, ?string $cwd = null, array $env = [], array $options = []):Promise {
    return call(function() use ($command, $cwd, $env, $options) {
        $process = new Process($command, $cwd, $env, $options);
        yield $process->start();
        $pout   = $process->getStdout();
        $perr   = $process->getStderr();
        $result = yield buffer($pout);
        $result .= yield buffer($perr);
        yield $process->join();
        return $result;
    });
}


/**
 * Print an array as an ascii table (recursively).
 * @param array $input       the input array.
 * @param bool  $lineCounter if true a number will be visible for each line inside the ascii table.
 * @param  ?callable(AsciiTable $table, int $lvl):void $intercept   intercept the main table and each subtable.<br />
 *                                   This closure will be passed 2 parameters: the AsciiTable and the current depth level.
 * @param  int    $lvl the depth level will start counting from this value on.
 * @return string the resulting ascii table.
 */
function tableFromArray(array $input, bool $lineCounter = false, ?callable $intercept = null, int $lvl = 0): string {
    $table = AsciiTable::create();
    if (null !== $intercept) {
        $intercept($table, $lvl);
    }
    $table->add("Key", "Value");
    foreach ($input as $key => &$item) {
        if (is_array($item)) {
            $table->add($key, tableFromArray($item, $lineCounter, $intercept, $lvl + 1));
            continue;
        } else {
            if (is_object($item)) {
                $table->add($key, get_class($item));
                continue;
            }
        }
        $table->add($key, $item);
    }

    $table->countLines($lineCounter);
    return $table->__toString();
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
 * @param  string      $prompt message to display along with the input request.
 * @return string|null
 */
function readLineSilent(string $prompt): ?string {
    if (preg_match('/^win/i', PHP_OS)) {
        $vbscript = sys_get_temp_dir().'prompt_password.vbs';
        file_put_contents(
            $vbscript,
            'wscript.echo(InputBox("'
            .addslashes($prompt)
            .'", "", "password here"))'
        );
        $command  = "cscript //nologo ".escapeshellarg($vbscript);
        $password = rtrim(shell_exec($command));
        unlink($vbscript);
    } else {
        $command = "/usr/bin/env bash -c 'echo OK'";
        if (rtrim(shell_exec($command)) !== 'OK') {
            trigger_error("Can't invoke bash");
            return null;
        }
        $command = "/usr/bin/env bash -c 'read -s -p \""
            .addslashes($prompt)
            ."\" mypassword && echo \$mypassword'";
        $password = rtrim(shell_exec($command));
        echo "\n";
    }
    return $password;
}

/**
 * Resolve on the next event loop iteration.
 * @return Deferred
 */
function deferred():Deferred {
    return new Deferred;
}


/**
 * Read input from the user.
 * 
 * This is a replacement for the default `\readline()` function which does not work in a PHAR program.
 * @param  string          $prompt You may specify a string with which to prompt the user.
 * @param  bool            $silent if true, the user input will be hidden.
 * @throws Error
 * @return Promise<string>
 */
function readline(string $prompt = '', bool $silent = false):Promise {
    static $input  = false;
    static $output = false;

    if (!$input) {
        $input = stream(STDIN);
    }

    if (!$output) {
        $output = stream(STDOUT);
    }

    return call(function() use ($prompt, $silent, $input, $output) {
        $hide    = "\033[0K\r";
        $watcher = $silent;
        yield $output->write($prompt);
        if ($silent) {
            call(function() use ($prompt, $output, $hide, &$watcher) {
                while ($watcher) {
                    yield $output->write($prompt);
                    yield delay(1);
                    if ($watcher) {
                        yield $output->write($hide);
                    }
                }
            });
        }
        /** @var string */
        $data = yield $input->read();
        if ($silent) {
            yield $output->write($hide);
        }
        $watcher = false;
        return $data;
    });
}