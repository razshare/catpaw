<?php
namespace CatPaw;

use FilesystemIterator;
use function React\Async\await;
use React\Promise\Promise;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Throwable;

class Directory {
    /**
     * Delete a file or directory recursively.
     * @param  string       $directoryName name of the directory.present.
     * @return Unsafe<void>
     */
    public static function delete(string $directoryName):Unsafe {
        $directoryName = realpath($directoryName);
        try {
            $directory = new RecursiveDirectoryIterator($directoryName);
        } catch(Throwable $e) {
            return error($e);
        }

        $iterator = new RecursiveIteratorIterator($directory);

        $directories = [];

        for ($iterator->rewind();$iterator->valid();$iterator->next()) {
            foreach ($iterator->current() as $fileName) {
                if ($error = File::delete($fileName)->error) {
                    return error($error);
                }
                $currentDirectoryName = dirname($fileName);
                if (!isset($directories[$currentDirectoryName])) {
                    $directories[$currentDirectoryName] = true;
                }
            }
        }

        foreach (array_keys($directories) as $directoryName) {
            if ($error = File::delete($directoryName)->error) {
                return error($error);
            }
        }

        return ok();
    }

    /**
     * @return Unsafe<void>
     */
    public static function create(string $directoryName):Unsafe {
        if ('' === $directoryName) {
            return error("Directory name cannot be empty.");
        }

        if (File::exists($directoryName)) {
            if (!isDirectory($directoryName)) {
                return error("The given directory $directoryName is actually an already existing file.");
            }
            return ok();
        }

        if (mkdir($directoryName, 0777, true)) {
            return error("Could not create directory $directoryName.");
        }

        return ok();
    }

    /**
     * List all files inside a directory recursively.
     * @param  string                $directoryName directory to scan.
     * @param  string                $pattern       regex pattern to match while scanning.
     * @return Unsafe<array<string>>
     */
    public static function flat(string $directoryName, false|string $pattern = false):Unsafe {
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
            /** @var FileInfo */
            $fileInfo = $iterator->current();
            $fileName = $fileInfo->getFilename();
            $filePath = $fileInfo->getRealPath();
            if ('.' === $fileName || '..' === $fileName) {
                continue;
            }
            $fileNames[] = realpath($filePath);
        }

        return ok($fileNames);
    }

    /**
     * List files and directories in a directory.
     * @param  string                $directoryName directory to scan.
     * @param  string                $pattern       regex pattern to match while scanning.
     * @return Unsafe<array<string>>
     */
    public static function list(string $directoryName, false|string $pattern = false):Unsafe {
        try {
            $iterator = new FilesystemIterator($directoryName);
        } catch(Throwable $e) {
            return error($e);
        }

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
            /** @var FileInfo */
            $fileInfo = $iterator->current();
            $fileName = $fileInfo->getFilename();
            $filePath = $fileInfo->getRealPath();
            if ('.' === $fileName || '..' === $fileName) {
                continue;
            }
            $fileNames[] = realpath($filePath);
        }

        return ok($fileNames);
    }


    /**
     * Copy a directory.
     * @param  string                $from
     * @param  string                $to
     * @param  string                $pattern regex pattern to match while scanning.
     * @return Promise<Unsafe<void>>
     */
    function copy(string $from, string $to, false|string $pattern = false):Promise {
        return new Promise(static function($ok) use ($from, $to, $pattern) {
            if (!isDirectory($from)) {
                $ok(error("Directory $from not found."));
                return;
            }
            
            try {
                $iterator = new FilesystemIterator($from);
            } catch(Throwable $e) {
                $ok(error($e));
                return;
            }

            if (false !== $pattern) {
                $iterator = new RegexIterator(
                    $iterator,
                    $pattern,
                    RecursiveRegexIterator::GET_MATCH
                );
            }

            $key = str_starts_with($from, './')?substr($from, 1):$from;

            for ($iterator->rewind();$iterator->valid();$iterator->next()) {
                foreach ($iterator->current() as $fileName) {
                    $parts            = explode($key, $fileName, 2);
                    $relativeFileName = end($parts);
                    if ($error = await(File::copy($fileName, "$to/$relativeFileName"))->error) {
                        $ok(error($error));
                        return;
                    }
                }
            }
            $ok(ok());
        });
    }

    private function __construct() {
    }
}