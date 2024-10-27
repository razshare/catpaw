<?php
namespace CatPaw\Core;

use function Amp\File\createDirectoryRecursively;
use function Amp\File\deleteDirectory;
use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\listFiles;

use FilesystemIterator;
use RegexIterator;
use Throwable;

class Directory {
    /**
     * Delete a directory recursively.
     * @param  string       $directoryName name of the directory to delete.
     * @return Result<None>
     */
    public static function delete(string $directoryName):Result {
        if (!$directoryName) {
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
                File::delete($fileName)->unwrap($error);
                if ($error) {
                    return error($error);
                }
            } else {
                self::delete($fileName)->unwrap($error);
                if ($error) {
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
     * Create a directory recursively.
     * @param  string       $directoryName the directory path.
     * @param  int          $mode          the permissions are 0777 by default, which means the widest possible access. For more information on permissions, read the details on the [chmod()](https://www.php.net/manual/en/function.chmod.php) page.
     * @return Result<None>
     */
    public static function create(string $directoryName, int $mode = 0777):Result {
        try {
            createDirectoryRecursively($directoryName, $mode);
            return ok();
        } catch (Throwable $error) {
            return error($error);
        }
    }

    /**
     * List all files inside a directory recursively.
     * @param  string                $directoryName the directory path.
     * @return Result<array<string>>
     */
    public static function flat(string $directoryName):Result {
        try {
            $result = [];
            $list   = Directory::list($directoryName)->unwrap($error);
            if ($error) {
                return error($error);
            }
            foreach ($list as $fileName) {
                if (isFile($fileName)) {
                    $result[] = $fileName;
                } else {
                    $flatList = Directory::flat($fileName)->unwrap($error);
                    if ($error) {
                        return error($error);
                    }
                    $result = [...$result, ...$flatList];
                }
            }
            return ok($result);
        } catch(Throwable $e) {
            return error($e);
        }
    }

    /**
     * List files and directories in a directory.
     * @param  string                    $directoryName the directory path.
     * @return Result<array<int,string>>
     */
    public static function list(string $directoryName):Result {
        try {
            if (!str_ends_with($directoryName, DIRECTORY_SEPARATOR)) {
                $directoryName = $directoryName.DIRECTORY_SEPARATOR;
            }

            $list   = listFiles($directoryName);
            $result = [];
            foreach ($list as $fileName) {
                $result[] = "$directoryName$fileName";
            }
            return ok($result);
        } catch (Throwable $e) {
            return error($e);
        }
    }


    /**
     * Copy a directory.
     * @param  string       $from
     * @param  string       $to
     * @param  false|string $pattern regex pattern to match while scanning.
     * @return Result<None>
     */
    public static function copy(string $from, string $to, false|string $pattern = false):Result {
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
                RegexIterator::GET_MATCH
            );
        }

        $key = str_starts_with($from, './')?substr($from, 1):$from;

        for ($iterator->rewind();$iterator->valid();$iterator->next()) {
            foreach ($iterator->current() as $fileName) {
                $parts            = explode($key, $fileName, 2);
                $relativeFileName = end($parts);
                File::copy($fileName, "$to/$relativeFileName")->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
        }
        return ok();
    }

    private function __construct() {
    }
}
