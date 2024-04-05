<?php

namespace CatPaw\Web;

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
    public static function create():self {
        return new self();
    }

    private function __construct() {
    }

    /**
     * @param  string ...$path
     * @return Unsafe
     */
    public function loadFromFile(string $fileName):Unsafe {
        $name              = $fileName;
        $key               = $name;
        $this->keys[$name] = $key;
        $file              = File::open($fileName)->try($error);
        if ($error) {
            return error($error);
        }
        $code = $file->readAll()->try($error);
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
