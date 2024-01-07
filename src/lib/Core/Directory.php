<?php
namespace CatPaw;

use function Amp\async;
use function Amp\File\createDirectory;
use function Amp\File\createDirectoryRecursively;
use function Amp\File\deleteDirectory;
use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\listFiles;
use Amp\Future;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use SplFileInfo;
use Throwable;

class Directory {
    /**
     * Delete a directory recursively.
     * @param  string               $directoryName name of the directory.present.
     * @return Unsafe<void>
     */
    public static function delete(string $directoryName):Unsafe {
        if (false === $directoryName) {
            return error("Invalid directory $directoryName.");
        }
        
        try {
            $list = listFiles($directoryName);
        } catch (Throwable $e) {
            return error($e);
        }

        foreach ($list as $fileNameLocal) {
            $fileName = "$directoryName/$fileNameLocal";
            if (isFile($fileName)) {
                if($error = File::delete($fileName)->error){
                    return error($error);
                }
            } else {
                if($error = self::delete($fileName)->error){
                    return error($error);
                }
            }
        }

        try {
            deleteDirectory($directoryName);
        } catch (Throwable $e) {
            return error($e);
        }

        return ok();
    }

    /**
     * @return Unsafe<void>
     */
    public static function create(string $directoryName, int $mode = 0777):Unsafe {
        try {
            return ok(createDirectoryRecursively($directoryName, $mode));
        } catch (Throwable $e) {
            return error($e);
        }
    }

    /**
     * List all files inside a directory recursively.
     * @param  string                $directoryName directory to scan.
     * @param  string                $pattern       regex pattern to match while scanning.
     * @return Unsafe<array<string>>
     */
    public static function flat(string $directoryName):Unsafe {
        try {
            $result = [];
            $listAttempt = Directory::list($directoryName);
            if($listAttempt->error){
                return error($listAttempt->error);
            }
            foreach ($listAttempt->value as $fileName) {
                if(isFile($fileName)){
                    $result[] = $fileName;
                } else {
                    $flatAttempt = Directory::flat($fileName);
                    if($flatAttempt->error){
                        return error($flatAttempt->error);
                    }
                    $result = [...$result, ...$flatAttempt->value];
                }
            }
            return ok($result);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * List files and directories in a directory.
     * @param  string                $directoryName directory to scan.
     * @return Unsafe<array<string>>
     */
    public static function list(string $directoryName):Unsafe {
        try {
            if(false === $directoryName){
                return error("Directory $directoryName not found.");
            }
            $list = listFiles($directoryName);
            $result = [];
            foreach ($list as $fileName) {
                $result[] = "$directoryName/$fileName";
            }
            return ok($result);
        } catch (\Throwable $e) {
            return error($e);
        }
    }


    /**
     * Copy a directory.
     * @param  string               $from
     * @param  string               $to
     * @param  string               $pattern regex pattern to match while scanning.
     * @return Future<Unsafe<void>>
     */
    function copy(string $from, string $to, false|string $pattern = false):Future {
        return async(static function() use ($from, $to, $pattern) {
            if (!isDirectory($from)) {
                return error("Directory $from not found.");
            }
            
            try {
                $iterator = new FilesystemIterator($from);
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

            $key = str_starts_with($from, './')?substr($from, 1):$from;

            for ($iterator->rewind();$iterator->valid();$iterator->next()) {
                foreach ($iterator->current() as $fileName) {
                    $parts            = explode($key, $fileName, 2);
                    $relativeFileName = end($parts);
                    if ($error = File::copy($fileName, "$to/$relativeFileName")->await()->error) {
                        return error($error);
                    }
                }
            }
            return ok();
        });
    }

    private function __construct() {
    }
}