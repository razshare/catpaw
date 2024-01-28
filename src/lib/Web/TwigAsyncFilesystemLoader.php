<?php

namespace CatPaw\Web;

use CatPaw\Core\Directory;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use Twig\Loader\LoaderInterface;
use Twig\Source;

class TwigAsyncFilesystemLoader implements LoaderInterface {
    /**
     * @param  string                            $directoryName
     * @param  callable(string):string           $mapNames
     * @return Unsafe<TwigAsyncFilesystemLoader>
     */
    public static function create(string $directoryName, callable $mapNames):Unsafe {
        $loader = new self();
        $loader->loadAllFromDirectory(directoryName:$directoryName, mapNames:$mapNames)->try($error);
        if ($error) {
            return error($error);
        }
        return ok($loader);
    }

    private function __construct() {
    }

    /**
     * @param  string ...$path
     * @return Unsafe
     */
    public function loadFromFile(string $fileName):Unsafe {
        if (!str_ends_with($fileName, '.twig')) {
            return error("File name `$fileName` does not end with `.twig`.");
        }
        $name              = $fileName;
        $key               = $name;
        $this->keys[$name] = $key;
        $file              = File::open($fileName)->try($error);
        if ($error) {
            return error($error);
        }
        $code = $file->readAll()->await()->try($error);
        if ($error) {
            return error($error);
        }
        $this->sources[$key] = new Source(code: $code, name: $name, path: $fileName);
        return ok();
    }

    /** @var array<string,string> $keys */
    private array $keys = [];

    /** @var array<string, Source> $sources */
    private array $sources = [];

    /**
     * @param  string                  $directoryName
     * @param  callable(string):string $mapNames
     * @return Unsafe<void>
     */
    public function loadAllFromDirectory(string $directoryName, callable $mapNames): Unsafe {
        $fileNames = Directory::flat(directoryName: $directoryName)->try($error);
        if ($error) {
            return error($error);
        }

        foreach ($fileNames as $fileName) {
            if (!str_ends_with($fileName, '.twig')) {
                continue;
            }
            $name              = $mapNames($fileName);
            $key               = $name;
            $this->keys[$name] = $key;
            $file              = File::open($fileName)->try($error);
            if ($error) {
                return error($error);
            }
            $code = $file->readAll()->await()->try($error);
            if ($error) {
                return error($error);
            }
            $this->sources[$key] = new Source(code: $code, name: $name, path: $fileName);
        }

        return ok();
    }

    public function getSourceContext(string $name): Source {
        return $this->sources[$this->keys[$name]];
    }

    public function getCacheKey(string $name): string {
        return $this->keys[$name];
    }

    public function isFresh(string $name, int $time): bool {
        return true;
    }

    public function exists(string $name):bool {
        return isset($this->keys[$name]);
    }
}
