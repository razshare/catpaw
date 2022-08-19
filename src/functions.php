<?php
namespace CatPaw;

use function Amp\call;
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

use Amp\Promise;
use CatPaw\Utilities\Stream;
use InvalidArgumentException;

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
 * @param  string                 $path directory to scan
 * @return Promise<array<string>>
 */
function listFilesRecursively(string $path):Promise {
    if (!\str_ends_with($path, '/')) {
        $path .= '/';
    }
    return call(function() use ($path) {
        $items = yield listFiles($path);
        $files = [];
        foreach ($items as $item) {
            $filename = "$path$item";
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
 * @param  mixed    $resource
 * @param  null|int $chunkSize
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
 * @param  string                   $path name of the directory.present.
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
 * @return Promise<void>
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

