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
     * Ignore files and directories bundled into the `.phar` bundle when invoked from within a `.phar` bundle.\
     * This makes it so that the resulting file name is always relative to the host machine, instead of it being relative to the `.phar` bundle.
     * @return self
     */
    public function withoutPhar():self {
        $this->usingPhar = false;
        return $this;
    }

    public function __toString():string {
        if (isPhar()) {
            $phar             = Phar::running();
            $fileName         = self::asFileName($this->path);
            $fileNamePharless = str_replace("$phar/", '', $fileName);

            if ($this->usingPhar) {
                $fileNameRootless = str_replace(getcwd(), '', $fileName);
                $fileNameWithPhar = "$phar/$fileNameRootless";
                if (file_exists($fileNameWithPhar)) {
                    return $fileNameWithPhar;
                }
            }

            return self::absolutePath($fileNamePharless);
        } else {
            // exit();
            return self::absolutePath(self::asFileName($this->path));
        }
    }
}
