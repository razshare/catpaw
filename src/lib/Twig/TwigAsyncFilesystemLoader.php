<?php

namespace CatPaw\Twig;

use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use Twig\Loader\LoaderInterface;
use Twig\Source;

class TwigAsyncFilesystemLoader implements LoaderInterface {
    /**
     *
     * @return TwigAsyncFilesystemLoader
     */
    public static function create():self {
        return new self();
    }


    
    /**
     * @param  array<string,string> $aliases
     * @return void
     */
    private function __construct(
        private array $aliases = [],
    ) {
    }

    /**
     * Set an alias for a name.
     * @param  string $alias
     * @param  string $name
     * @return void
     */
    public function setAlias(string $alias, string $name):void {
        $this->aliases[$alias] = $name;
    }

    /**
     * Load source from file.
     * @param  string       $fileName
     * @return Unsafe<None>
     */
    public function loadFromFile(string $fileName):Unsafe {
        $this->keys[$fileName] = $fileName;
        $file                  = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $code = $file->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }
        $this->sources[$fileName] = new Source(code: $code, name: $fileName, path: $fileName);
        return ok();
    }

    /** @var array<string,string> $keys */
    private array $keys = [];

    /** @var array<string, Source> $sources */
    private array $sources = [];

    public function getSourceContext(string $name): Source {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        return $this->sources[$this->keys[$name]];
    }

    public function getCacheKey(string $name): string {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        return $this->keys[$name];
    }

    public function isFresh(string $name, int $time): bool {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        return true;
    }

    public function exists(string $name):bool {
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        return isset($this->keys[$name]);
    }
}
