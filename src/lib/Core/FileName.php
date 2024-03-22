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
    public static function create(array $path):self {
        return new self($path);
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
                    return realpath($localizedFileName);
                }
                return $pharFileName;
            }
            return realpath($localizedFileName);
        } else {
            return realpath(self::asFileName($this->path));
        }
    }
}
