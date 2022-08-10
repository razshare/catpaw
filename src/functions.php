<?php
namespace CatPaw;

use function Amp\call;
use function Amp\File\deleteDirectory;
use function Amp\File\deleteFile;
use function Amp\File\getStatus;
use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\listFiles;
use Amp\Promise;
use CatPaw\Utilities\Stream;
use InvalidArgumentException;

/**
 * List all files (not directories) inside a directory.
 * Dot entries are not included in the resulting array (i.e. "." and "..").
 * @param  string                 $path directory to scan
 * @return Promise<array<string>>
 */
function listFilesRecursive(string $path):Promise {
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
                foreach (yield listFilesRecursive($filename) as $subItem) {
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

function stream(
    mixed $resource,
    ?int $chunkSize = null,
) {
    return Stream::of($resource, $chunkSize);
}

/**
 * Delete a file or directory.
 * @param  string                   $path        name of the directory.
 * @param  bool                     $recursively if true will try remove all sub directories aswell.<br />
 *                                               <b>NOTE</b>: will fail if false and subdirectories are present.
 * @throws InvalidArgumentException if the specified directory name is not actually a directory.
 * @return Promise<void>
 */
function delete(string $path, bool $recursively = true):Promise {
    return call(function() use ($path, $recursively) {
        if (yield isFile($path)) {
            yield deleteFile($path);
            return;
        }

        if (!yield isDirectory($path)) {
            throw new InvalidArgumentException("\"$path\" is not a valid path.");
        }

        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        
        $files = yield listFiles($path);
        
        foreach ($files as $file) {
            yield delete($file, $recursively);
        }

        yield deleteDirectory($path);
    });
}

/**
 * Get the filenames within a directory recursively.
 * @param  string                              $path startup directory.
 * @param  array|null                          $map  an associative array that will hold your results.
 * @return Promise<array<array<string,mixed>>>
 */
function listFilesInfoRecursive(string $path, array|false $ignore = false):Promise {
    return call(function() use ($path, $ignore) {
        $list = [];
        $path = preg_replace('/\/++/', '/', $path);
        //$fn = end(explode("/",$root));
        if (yield isDirectory($path)) {
            foreach (yield listFiles($path) as $a => $file) {
                if ('.' == $file || '..' == $file || \in_array($file, $ignore)) {
                    continue;
                }
                
                $list = [ ...$list, ...yield listFilesInfoRecursive("$path/$file")];
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

