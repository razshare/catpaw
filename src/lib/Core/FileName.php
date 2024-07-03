<?php

namespace CatPaw\Core;

use Phar;
use Stringable;

class FileName implements Stringable {
    /**
     * Create a file name starting from a `$base` directory.
     * @param  array<string> $base
     * @return FileName
     */
    public static function create(array $base):self {
        return new self($base);
    }

    private bool $usingPhar = true;

    /**
     * @param array<string> $path
     */
    private function __construct(private array $path) {
    }

    /**
     * Given a `$path`, create a file name.
     * @param  array<string> $path
     * @return string
     */
    private static function asFileName(array $path):string {
        $parts = [];
        $count = count($path);
        for ($index = 0; $index < $count; $index++) {
            $pathName = $path[$index];
            if ($index < $count - 1 && !str_ends_with($pathName, '/')) {
                $pathName = "$pathName/";
            }
            $parts[] = $pathName;
        }
        return join($parts)?:'';
    }

    private static function absolutePath(string $path):string {
        $root = match (true) {
            str_starts_with($path, './')  => './',
            str_starts_with($path, '../') => '../',
            str_starts_with($path, '/')   => '/',
            default                       => '',
        };

        $path  = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), function($item) {
            return (bool)strlen($item);
        });
        $absolutes = [];
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return $root.implode(DIRECTORY_SEPARATOR, $absolutes);
    }

    /**
     * Lookup the file in in the `.phar` before falling back to the file system.
     * @return self
     */
    public function withoutPhar(): self {
        $this->usingPhar = false;
        return $this;
    }

    public function __toString(): string {
        if (isPhar()) {
            $phar              = Phar::running();
            $localizedFileName = str_replace("$phar/", '', self::asFileName($this->path));

            if ($this->usingPhar) {
                $pharFileName = "$phar/$localizedFileName";
                if (!file_exists($pharFileName)) {
                    return self::absolutePath($localizedFileName);
                }
                return $pharFileName;
            }
            return self::absolutePath($localizedFileName);
        } else {
            return self::absolutePath(self::asFileName($this->path));
        }
    }
}
