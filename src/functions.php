<?php
namespace CatPaw;

use function Amp\ByteStream\buffer;

use Amp\DeferredFuture;
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

use Amp\Future;
use Amp\Process\Process;
use CatPaw\Utilities\AsciiTable;
use CatPaw\Utilities\Stream;
use Closure;
use Error;
use InvalidArgumentException;
use Phar;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Revolt\EventLoop;

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
 * @param  string        $path
 * @param  array|false   $ignore
 * @return array<string>
 */
function listFilesRecursively(string $path, array|false $ignore = false):array {
    if (!\str_ends_with($path, '/')) {
        $path .= '/';
    }
    $filenames = listFiles($path);
    $files     = [];
    foreach ($filenames as $filename) {
        if ($ignore && in_array($filename, $ignore)) {
            continue;
        }

        $filename = "$path$filename";
        $isDir    = isDirectory($filename);
        if ($isDir) {
            foreach (listFilesRecursively($filename) as $subItem) {
                $files[] = $subItem;
            }
            continue;
        }

        $files[] = $filename;
    }
    return $files;
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
 * Convert a resource into a stream.
 * @param  mixed  $resource
 * @param  ?int   $chunkSize
 * @return Stream
 */
function stream(
    mixed $resource,
    int $chunkSize = -1,
):Stream {
    return Stream::of($resource, $chunkSize < 0?null:$chunkSize);
}

/**
 * Delete a file or directory recursively.
 * @param  string                   $path   name of the directory.present.
 * @param  array|false              $ignore a map of file/directory names to ignore.
 * @throws InvalidArgumentException if the specified directory name is not actually a directory.
 * @return void
 */
function deleteDirectoryRecursively(string $path, array|false $ignore = false):void {
    if (!isDirectory($path)) {
        throw new InvalidArgumentException("\"$path\" is not a valid directory.");
    }

    if (!str_ends_with($path, '/')) {
        $path .= '/';
    }
        
    $files = listFiles($path);
        
    foreach ($files as $filename) {
        if (false !== $ignore && in_array($filename, $ignore)) {
            continue;
        }

        $target = "$path$filename";

        if (isFile($target)) {
            deleteFile($target);
            continue;
        }

        deleteDirectoryRecursively($target);
    }

    deleteDirectory($path);
}

/**
 * Copy a file.
 * @param  string $from
 * @param  string $to
 * @return void
 */
function copyFile(string $from, string $to):void {
    write($to, read($from));
}

/**
 * Copy a file or directory recursively.
 * @param  string      $from
 * @param  string      $to
 * @param  array|false $ignore a map of file/directory names to ignore.
 * @return void
 */
function copyDirectoryRecursively(string $from, string $to, array|false $ignore = false):void {
    if (!str_ends_with($from, '/')) {
        $from .= '/';
    }

    if (!str_ends_with($to, '/')) {
        $to .= '/';
    }

    if (!exists($to)) {
        createDirectoryRecursively($to);
    }

    foreach (listFiles($from) as $filename) {
        if (false !== $ignore && in_array($filename, $ignore)) {
            continue;
        }

        $source      = "$from$filename";
        $destination = "$to$filename";

        if (isFile($source)) {
            copyFile($source, $destination);
            continue;
        }

        copyDirectoryRecursively($source, $destination);
    }
}

/**
 * Get the filenames within a directory recursively.
 * @param  string                     $path   startup directory.
 * @param  array|false                $ignore a map of file/directory names to ignore.
 * @return array<array<string,mixed>>
 */
function listFilesInfoRecursively(string $path, array|false $ignore = false):array {
    $list = [];
    if (isDirectory($path)) {
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        foreach (listFiles($path) as $i => $filename) {
            if (false !== $ignore && \in_array($filename, $ignore)) {
                continue;
            }              
            $list = [ ...$list, ...listFilesInfoRecursively("$path$filename")];
        }
    } else {
        $list = [
            ...$list,
            getStatus($path)
        ];
    }

    return $list;
}




/**
 * Create a process, run it, wait for it to end and get the output.
 * @param  string $command
 * @param  string $cwd
 * @param  array  $env
 * @param  array  $options
 * @return string the output data of the process.
 */
function execute(
    string $command,
    string $cwd = '',
    array $env = [],
    array $options = []
):string {
    $process = Process::start($command, $cwd?$cwd:null, $env, $options);
    $pout    = $process->getStdout();
    $perr    = $process->getStderr();
    $result  = buffer($pout);
    $result .= buffer($perr);
    $process->join();
    return $result;
}


/**
 * Print an array as an ascii table (recursively).
 * @param array $input       the input array.
 * @param bool  $lineCounter if true a number will be visible for each line inside the ascii table.
 * @param  false|callable(AsciiTable $table, int $lvl):void $intercept   intercept the main table and each subtable.<br />
 *                                   This closure will be passed 2 parameters: the AsciiTable and the current depth level.
 * @param  int    $lvl the depth level will start counting from this value on.
 * @return string the resulting ascii table.
 */
function tableFromArray(
    array $input,
    bool $lineCounter = false,
    false|callable $intercept = false,
    int $lvl = 0
): string {
    $table = AsciiTable::create();
    if ($intercept) {
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
 * @param  string $prompt message to display along with the input request.
 * @return string
 */
function readLineSilent(string $prompt): string {
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
            return '';
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
 * @return Future<void>
 */
function deferred():Future {
    return (new DeferredFuture)->getFuture()->complete();
}


/**
 * Read input from the user.
 * 
 * This is a replacement for the default `\readline()` function which does not work in a PHAR program.
 * @param string                               $prompt   prompt the user.
 * @param (callable(string):bool|string)|false $validate validate the input value.
 * 
 * Return `true` to indicate that the input is valid or `false` to indicate the input is not valid.
 * 
 * **Note**: instead of `false`, you may instead return a feedback `string` to invalidate the input.
 * 
 * ## Example:
 * ```php
 * readline("Pick a natural number between 1 and 3: ", fn($value)=>$value === '2'?true:"$value is not a natural number between 1 and 3, try again.")
 * ```
 * @param  bool   $silent if true, the user input will be hidden.
 * @throws Error
 * @return string
 */
function readline(
    string $prompt = '',
    false|callable $validate = false,
    bool $silent = false,
):string {
    static $input  = false;
    static $output = false;

    if (!$input) {
        $input = stream(STDIN);
    }

    if (!$output) {
        $output = stream(STDOUT);
    }

    $hide      = "\033[0K\r";
    $watcher   = $silent;
    $watcherID = false;
    $output->write($prompt);
    if ($silent) {
        $watcherID = EventLoop::repeat(0.001, function() use ($prompt, $output, $hide, &$watcher) {
            $output->write($prompt);
            if ($watcher) {
                $output->write($hide);
            }
        });
    }
    /** @var string */
    $data = $input->read();
    if ($silent) {
        $output->write($hide);
    }
    $watcher = false;
    EventLoop::cancel($watcherID);
    $result = preg_replace('/\n$/i', '', $data);

    if ($validate) {
        $validation = $validate($result);
        if (is_string($validation)) {
            $output->write($validation.PHP_EOL);
            return readline($prompt, $validate, $silent);
        } else if (is_bool($validation)) {
            if (!$validation) {
                return readline($prompt, $validate, $silent);
            }
        } else {
            throw new Error("The validation function must return either a boolean or a string.");
        }
    }
    return $result;
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