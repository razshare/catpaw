<?php

namespace CatPaw\Amp\File;

use Amp\File\Driver\BlockingFile;
use Amp\File\File;
use Amp\File\FilesystemDriver;
use Amp\File\FilesystemException;
use Throwable;

final class CatPawDriver implements FilesystemDriver {
    public function openFile(string $path, string $mode): File {
        $mode = \str_replace(['b', 't', 'e'], '', $mode);

        switch ($mode) {
            case "r":
            case "r+":
            case "w":
            case "w+":
            case "a":
            case "a+":
            case "x":
            case "x+":
            case "c":
            case "c+":
                break;

            default:
                throw new \Error("Invalid file mode: {$mode}");
        }

        try {
            \set_error_handler(static function($type, $message) use ($path, $mode) {
                throw new FilesystemException("Failed to open '{$path}' in mode '{$mode}': {$message}");
            });

            if (!$handle = \fopen($path, $mode.'be')) {
                throw new FilesystemException("Failed to open '{$path}' in mode '{$mode}'");
            }
            
            return new BlockingFile($handle, $path, $mode);
        } finally {
            \restore_error_handler();
        }
    }

    
    public function getStatus(string $path): ?array {
        \clearstatcache(true, $path);
        return @\stat($path) ?: null;
    }

    public function getLinkStatus(string $path): ?array {
        \clearstatcache(true, $path);
        return @\lstat($path) ?: null;
    }


    public function createSymlink(string $target, string $link): void {
        try {
            \set_error_handler(static function($type, $message) use ($target, $link) {
                throw new FilesystemException("Could not create symbolic link '{$link}' to '{$target}': {$message}");
            });

            if (!\symlink($target, $link)) {
                throw new FilesystemException("Could not create symbolic link '{$link}' to '{$target}'");
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function createHardlink(string $target, string $link): void {
        try {
            \set_error_handler(static function($type, $message) use ($target, $link) {
                throw new FilesystemException("Could not create hard link '{$link}' to '{$target}': {$message}");
            });

            if (!\link($target, $link)) {
                throw new FilesystemException("Could not create hard link '{$link}' to '{$target}'");
            }
        } finally {
            \restore_error_handler();
        }
    }

    /**
     * @psalm-suppress ParamNameMismatch
     */
    public function resolveSymlink(string $path): string {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Could not resolve symbolic link '{$path}': {$message}");
            });

            if (false === ($result = \readlink($path))) {
                throw new FilesystemException("Could not resolve symbolic link '{$path}'");
            }

            return $result;
        } finally {
            \restore_error_handler();
        }
    }

    public function move(string $from, string $to): void {
        try {
            \set_error_handler(static function($type, $message) use ($from, $to) {
                throw new FilesystemException("Could not move file from '{$from}' to '{$to}': {$message}");
            });

            if (!\rename($from, $to)) {
                throw new FilesystemException("Could not move file from '{$from}' to '{$to}'");
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function deleteFile(string $path): void {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Could not delete file '{$path}': {$message}");
            });

            if (!\unlink($path)) {
                throw new FilesystemException("Could not delete file '{$path}'");
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function createDirectory(string $path, int $mode = 0777): void {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Could not create directory '{$path}': {$message}");
            });

            /** @noinspection MkdirRaceConditionInspection */
            if (!\mkdir($path, $mode)) {
                throw new FilesystemException("Could not create directory '{$path}'");
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function createDirectoryRecursively(string $path, int $mode = 0777): void {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                if (!\is_dir($path)) {
                    throw new FilesystemException("Could not create directory '{$path}': {$message}");
                }
            });

            if (\is_dir($path)) {
                return;
            }

            /** @noinspection MkdirRaceConditionInspection */
            if (!\mkdir($path, $mode, true)) {
                if (\is_dir($path)) {
                    return;
                }

                throw new FilesystemException("Could not create directory '{$path}'");
            }

            return;
        } finally {
            \restore_error_handler();
        }
    }

    public function deleteDirectory(string $path): void {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Could not remove directory '{$path}': {$message}");
            });

            if (!\rmdir($path)) {
                throw new FilesystemException("Could not remove directory '{$path}'");
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function listFiles(string $path): array {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Failed to list files in '{$path}': {$message}");
            });

            if (!\is_dir($path)) {
                throw new FilesystemException("Failed to list files; '{$path}' is not a directory");
            }

            if ($arr = \scandir($path)) {
                \clearstatcache(true, $path);

                return \array_values(\array_filter($arr, static function($el) {
                    return "." !== $el && ".." !== $el;
                }));
            }

            throw new FilesystemException("Failed to list files in '{$path}'");
        } finally {
            \restore_error_handler();
        }
    }

    public function changePermissions(string $path, int $mode): void {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Failed to change permissions for '{$path}': {$message}");
            });

            if (!\chmod($path, $mode)) {
                throw new FilesystemException("Failed to change permissions for '{$path}'");
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function changeOwner(string $path, ?int $uid, ?int $gid): void {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Failed to change owner for '{$path}': {$message}");
            });

            if (-1 !== ($uid ?? -1) && !\chown($path, $uid)) {
                throw new FilesystemException("Failed to change owner for '{$path}'");
            }

            if (-1 !== ($gid ?? -1) && !\chgrp($path, $gid)) {
                throw new FilesystemException("Failed to change owner for '{$path}'");
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function touch(string $path, ?int $modificationTime, ?int $accessTime): void {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Failed to touch '{$path}': {$message}");
            });

            $modificationTime = $modificationTime ?? \time();
            $accessTime       = $accessTime       ?? $modificationTime;

            if (!\touch($path, $modificationTime, $accessTime)) {
                throw new FilesystemException("Failed to touch '{$path}'");
            }
        } finally {
            \restore_error_handler();
        }
    }


    public function read(string $path): string {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Failed to read '{$path}': {$message}");
            });

            $file     = $this->openFile($path, 'r');
            $contents = '';
            try {
                while ($result = $file->read()) {
                    $contents .= $result;
                }
                return $contents;
            } catch (Throwable) {
                throw new FilesystemException("Failed to read '{$path}'");
                $file->close();
                return $contents;
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function write(string $path, string $contents): void {
        try {
            \set_error_handler(static function($type, $message) use ($path) {
                throw new FilesystemException("Failed to read '{$path}': {$message}");
            });

            $file = $this->openFile($path, 'w+');
            try {
                $file->write($contents);
            } catch (Throwable) {
                throw new FilesystemException("Failed to read '{$path}'");
                $file->close();
            }
        } finally {
            \restore_error_handler();
        }
    }
}
