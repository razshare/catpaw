<?php

namespace CatPaw\Core;

use Phar;
use Stringable;

/**
 * A `FileName` is an object that will stringify itself 
 * into `$path` when cast to `string`.\
 * The unique feature of `FileName` is that it will 
 * automatically detect if `$path`
 * is included in the current `.phar` bundle and it 
 * will return the correct string according to `.phar` semantics.
 * @package CatPaw\Core
 */
class FileName implements Stringable {
    /**
     * @param array<string> $path
     */
    public function __construct(private array $path) {
    }

    /**
     * Given a `$path`, create a file name.
     * @param  array<string> $path
     * @return string
     */
    private static function glue(array $path):string {
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

    private static function normalize(string $path):string {
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
        $normalized = [];
        foreach ($parts as $part) {
            if ('.' == $part) {
                continue;
            }
            if ('..' == $part) {
                array_pop($normalized);
            } else {
                $normalized[] = $part;
            }
        }
        return $root.implode(DIRECTORY_SEPARATOR, $normalized);
    }

    private bool $scanPhar = true;

    /**
     * Don't scan the `.phar` bundle,
     * just stringify to the original `$path`.
     * @return self
     */
    public function withoutPhar():self {
        $this->scanPhar = false;
        return $this;
    }
    
    private bool $absolute = false;

    /**
     * Convert to absolute file name.
     * @return FileName
     */
    public function absolute():self {
        $this->absolute = true;
        return $this;
    }

    private false|string $cache = false;

    public function __toString():string {
        if (false !== $this->cache) {
            return $this->cache;
        }

        if (isPhar()) {
            $phar             = Phar::running();
            $fileName         = self::glue($this->path);
            $fileNamePharless = str_replace("$phar/", '', $fileName);

            if ($this->scanPhar) {
                $fileNameRootless = str_replace(getcwd(), '', $fileName);
                if (str_starts_with($fileNameRootless, '/')) {
                    $fileNameRootless = substr($fileNameRootless, 1);
                }
                $fileNameWithPhar = "$phar/$fileNameRootless";
                if (file_exists($fileNameWithPhar)) {
                    return $this->cache = $fileNameWithPhar;
                }
            }

            return $this->cache = self::normalize($fileNamePharless);
        } else {
            if ($this->absolute) {
                $normalized = self::normalize(self::glue($this->path));
                $real       = realpath($normalized);
                if (false === $real) {
                    return $this->cache = $normalized;
                }
                return $this->cache = $real;
            }
            return $this->cache = self::normalize(self::glue($this->path));
        }
    }
}
