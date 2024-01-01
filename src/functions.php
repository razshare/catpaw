<?php
namespace CatPaw;

use Error;
use Exception;
use InvalidArgumentException;
use Phar;
use function React\Async\await;
use React\ChildProcess\Process;
use React\Promise\Promise;
use React\Stream\ReadableResourceStream;
use React\Stream\ThroughStream;
use React\Stream\WritableResourceStream;
use React\Stream\WritableStreamInterface;
use RecursiveArrayIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

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
 */
class Unsafe {
    /**
     * @param T           $value
     * @param false|Error $error
     */
    public function __construct(
        public readonly mixed $value,
        public readonly false|Error $error
    ) {
        if ($error && !($error instanceof Error)) {
            $this->error = new Error($error);
        }
    }
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


class Signal {
    private bool $busy = false;
    public static function create():self {
        return new self(LinkedList::create());
    }

    /**
     * @param LinkedList<callable(...mixed):void> $list
     */
    private function __construct(private LinkedList $list) {
    }

    /**
     * Send signal and trigger listeners.
     * @param int $code code to send, defaults to `SIGTERM`.
     * 
     */
    public function send($code = SIGTERM) {
        if ($this->busy) {
            return;
        }
        $this->busy = true;
        for ($this->list->rewind();$this->list->valid();$this->list->next()) {
            $function = $this->list->current();
            $function($code);
        }
        $this->busy = false;
    }

    /**
     * @param callable(int):void
     */
    public function listen(callable $function):void {
        $this->list->push($function);
    }

    /**
     * Clear all lsiteners.
     */
    public function clear() {
        $this->list->setIteratorMode(LinkedList::IT_MODE_DELETE);
        for ($this->list->rewind();$this->list->valid();$this->list->next()) {
            continue;
        }
    }
}


/**
 * Execute a command.
 * @param  string                        $command instruction to run.
 * @param  false|WritableStreamInterface $writer  send the output of the process to this stream.
 * @param  false|Signal                  $signal  when this signal is triggered the process is killed.
 * @return Promise<Unsafe<void>>
 */
function execute(string $command, false|WritableStreamInterface $writer = false, false|Signal $signal = false):Promise {
    return new Promise(function($ok) use ($command, $writer, $signal) {
        $process = new Process($command);

        try {
            $process->start();
        } catch(Throwable $e) {
            $ok(error($e->getMessage()));
            return;
        }

        if ($signal) {
            $signal->listen(static function($code) use ($process) {
                if ($process->isTerminated() || $process->isStopped()) {
                    return;
                }
                $process->terminate($code);
            });
        }

        if ($writer) {
            $process->stdout->on('data', static function($chunk) use ($writer) {
                $writer->write($chunk);
            });
        }
        
        $process->stdout->on('end', static function() use ($ok) {
            $ok(ok());
        });
        
        $process->stdout->on('error', static function(Exception $e) use ($ok) {
            $ok(error($e->getMessage()));
        });
        
        $process->stdout->on('close', static function() use ($ok) {
            $ok(ok());
        });
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

class File {
    /**
     * Copy a file.
     * @param  string                $from
     * @param  string                $to
     * @return Promise<Unsafe<void>>
     */
    function copyFile(string $from, string $to):Promise {
        $source = File::open($from);
        if ($source->error) {
            return error($source->error);
        }
        $destination = File::open($to);
        if ($destination->error) {
            return error($destination->error);
        }
        return $destination->value->writeStream($source->value->getStream());
    }

    /**
     * Copy a file or directory recursively.
     * @param  string      $from
     * @param  string      $to
     * @param  array|false $ignore a map of file/directory names to ignore.
     * @return Unsafe
     */
    function copyDirectoryRecursively(string $from, string $to, array|false $ignore = false):Unsafe {
        if (!str_ends_with($from, '/')) {
            $from .= '/';
        }

        if (!str_ends_with($to, '/')) {
            $to .= '/';
        }

        if (!File::exists($to)) {
            if ($error = File::createDirectoryRecursively($to)->error) {
                return error($error);
            }
        }

        $list = File::list($from);

        if ($list->error) {
            return error($list->error);
        }

        foreach ($list->value as $fileName) {
            if (false !== $ignore && in_array($fileName, $ignore)) {
                continue;
            }

            $source      = "$from$fileName";
            $destination = "$to$fileName";

            if (self::isFile($source)) {
                if ($error = await(self::copyFile($source, $destination))->error) {
                    return error($error);
                }
                continue;
            }

            
            if ($error = self::copyDirectoryRecursively($source, $destination)->error) {
                return error($error);
            }
        }
    }


    /**
     * List files and directories in a directory.
     * @param  string                $directoryName directory to scan.
     * @return Unsafe<array<string>>
     */
    public static function list(string $directoryName):Unsafe {
        if (!self::isDirectory($directoryName)) {
            return error("Directory $directoryName doesn't exist.");
        }

        $files = array_diff(scandir($directoryName), ['.', '..']);

        return ok($files?:[]);
    }

    /**
     * List all files inside a directory recursively.
     * @param  string                $directoryName directory to scan.
     * @param  string                $pattern       regex pattern to match while scanning.
     * @return Unsafe<array<string>>
     */
    public static function listFilesRecursively(string $directoryName, false|string $pattern = false):Unsafe {
        try {
            $directory = new RecursiveDirectoryIterator($directoryName);
        } catch(Throwable $e) {
            return error($e);
        }

        $iterator = new RecursiveIteratorIterator($directory);

        if (false !== $pattern) {
            $iterator = new RegexIterator(
                $iterator,
                $pattern,
                RecursiveRegexIterator::GET_MATCH
            );
        }

        /** @var array<string> */
        $fileNames = [];

        for ($iterator->rewind();$iterator->valid();$iterator->next()) {
            foreach ($iterator->current() as $fileName) {
                $fileNames[] = $fileName;
            }
        }

        return ok($fileNames);
    }


    /**
     * Get the filenames within a directory recursively.
     * @param  string                             $directoryName startup directory.
     * @param  string                             $pattern       regex pattern to match while scanning.
     * @return Unsafe<array<array<string,mixed>>>
     */
    function listFilesInfosRecursively(string $directoryName, false|string $pattern = false):Unsafe {
        $list = self::listFilesRecursively($directoryName, $pattern);
        if ($list->error) {
            return error($list->error);
        }

        $fileInfos = [];

        foreach ($list->value as $fileName) {
            $fileInfo = File::getStatus($fileName);
            if ($fileInfo->error) {
                return error($fileInfo->error);
            }
        }


        return ok($fileInfos);
    }

    /**
     * @return Unsafe<array>
     */
    public static function getStatus(string $fileName):Unsafe {
        $info = stat($fileName);
        if (false === $info) {
            return error("Could not get status of file $fileName.");
        }

        return ok($info);
    }

    /**
     * @return Unsafe<int>
     */
    public static function getModificationTime(string $fileName):Unsafe {
        $mtime = filemtime($fileName);
        if (false === $mtime) {
            return error("Could not find file $fileName modification time.");
        }
        return ok($mtime);
    }

    public static function exists(string $fileName):bool {
        return file_exists($fileName);
    }

    public static function isFile(string $fileName):bool {
        return is_file($fileName);
    }

    public static function isDirectory(string $fileName):bool {
        return is_dir($fileName);
    }

    public static function deleteFile(string $fileName):Unsafe {
        if (!unlink($fileName)) {
            return error("Could not delete file $fileName");
        }
        return ok();
    }
    
    /**
     * Delete a file or directory recursively.
     * @param  string                   $directoryName name of the directory.present.
     * @param  array|false              $ignore        a map of file/directory names to ignore.
     * @throws InvalidArgumentException if the specified directory name is not actually a directory.
     * @return Unsafe<void>
     */
    public function deleteDirectory(string $directoryName, array|false $ignore = false):Unsafe {
        if (!str_ends_with($directoryName, '/')) {
            $directoryName .= '/';
        }
            
        $list = self::list($directoryName);
            
        if ($list->error) {
            return error($list->error);
        }

        foreach ($list->value as $fileName) {
            if (false !== $ignore && in_array($fileName, $ignore)) {
                continue;
            }

            $target = "$directoryName$fileName";

            if (self::isFile($target)) {
                if ($error = self::deleteFile($target)->error) {
                    return error($error);
                }
                continue;
            }

            if ($error = self::deleteDirectory($target)->error) {
                return error($error);
            }
        }

        if ($error = self::deleteDirectory($directoryName)->error) {
            return error($error);
        }

        return ok();
    }

    /**
     * @return Unsafe
     */
    public static function createDirectoryRecursively(string $directoryName):Unsafe {
        if ('' === $directoryName) {
            return error("Directory name cannot be empty.");
        }

        if (self::exists($directoryName)) {
            if (!self::isDirectory($directoryName)) {
                return error("The given directory $directoryName is actually an already existing file.");
            }
            return ok();
        }

        $currentDirectoryName = '';

        foreach (explode('/', $directoryName) as $part) {
            $currentDirectoryName .= "/$part";
            if (self::exists($currentDirectoryName)) {
                continue;
            }

            $result = File::createDirectory($currentDirectoryName);
            if ($result->error) {
                return error($result->error);
            }
        }

        return ok();
    }

    /**
     * @return Unsafe
     */
    public static function createDirectory(string $directoryName):Unsafe {
        if (mkdir($directoryName)) {
            return error("Could not create directory $directoryName.");
        }
        return ok();
    }

    /**
     * @return Unsafe<File>
     */
    public static function open(string $fileName, string $mode = 'r'):Unsafe {
        if (!self::exists($fileName)) {
            return error("File $fileName not found.");
        }
        $file = fopen($fileName, $mode);
        if (!$file) {
            return error("Could not open file $fileName");
        }
        return ok(new self($file));
    }

    private ReadableResourceStream $reader;
    private WritableResourceStream $writer;
    private function __construct(
        private $stream
    ) {
    }


    private function setupReader():Unsafe {
        if (isset($this->reader)) {
            if (!$this->reader->isReadable()) {
                return error("The readable stream has already been closed, you cannot read anymore from this file.");
            }
            return ok();
        }
        $this->reader = new ReadableResourceStream($this->stream);
        return ok();
    }

    private function setupWriter():Unsafe {
        if (isset($this->writer)) {
            if ($this->writer->isWritable()) {
                return error("The writable stream has already been closed, you cannot write anymore to this file.");
            }
            return ok();
        }
        $this->writer = new WritableResourceStream($this->stream);
        return ok();
    }
    
    /**
     * @return Promise<Unsafe<void>>
     */
    public function writeContent(string $content):Promise {
        $setup = $this->setupWriter();

        if ($setup->error) {
            return new Promise(static fn ($ok) => $ok(error($setup->error)));
        }

        $writer = $this->writer;
        if (is_string($content)) {
            if ($writer->write($content)) {
                return new Promise(static fn ($ok) => $ok(ok()));
            }
            return new Promise(static fn ($ok) => $ok(error("Could not write to file.")));
        }
    }

    /**
     * @return Promise<Unsafe<void>>
     */
    public function writeStream(ReadableResourceStream $reader):Promise {
        $setup = $this->setupWriter();

        if ($setup->error) {
            return new Promise(static fn ($ok) => $ok(error($setup->error)));
        }

        $writer = $this->writer;

        $resolved = false;
        return new Promise(static function($ok) use ($reader, $writer, &$resolved) {
            $reader->on('data', static function($chunk) use ($writer) {
                $writer->write($chunk);
            });

            $reader->on('end', static function() use ($ok, $reader, &$resolved) {
                $ok(ok());
                $resolved = true;
                $reader->close();
            });
            
            $reader->on('error', static function(Exception $e) use ($ok, $reader, &$resolved) {
                $ok(error($e->getMessage()));
                $resolved = true;
                $reader->close();
            });
            
            $reader->on('close', static function() use ($ok, &$resolved) {
                if ($resolved) {
                    return;
                }
                $resolved = true;
                $ok(ok());
            });
        });
    }
    
    
    /**
     * @return Promise<Unsafe<string>>
     */
    public function readAll():Promise {
        $setup = $this->setupReader();

        if ($setup->error) {
            return new Promise(static fn ($ok) => $ok(error($setup->error)));
        }

        $reader   = $this->reader;
        $content  = '';
        $resolved = false;
        return new Promise(static function($ok) use ($reader, &$content, &$resolved) {
            $reader->on('data', static function($chunk) use (&$content) {
                $content .= $chunk;
            });

            $reader->on('end', static function() use ($ok, &$content, $reader, &$resolved) {
                $ok(ok($content));
                $resolved = true;
                $reader->close();
            });
            
            $reader->on('error', static function(Exception $e) use ($ok, $reader, &$resolved) {
                $ok(error($e->getMessage()));
                $resolved = true;
                $reader->close();
            });
            
            $reader->on('close', static function() use ($ok, &$content, &$resolved) {
                if ($resolved) {
                    return;
                }
                $resolved = true;
                $ok(ok($content));
            });
        });
    }

    public function getStream():ReadableResourceStream {
        return new ReadableResourceStream($this->stream);
    }

    public function close() {
        if ($this->reader) {
            $this->reader->close();
        }
        if ($this->writer) {
            $this->writer->close();
        }
    }
}