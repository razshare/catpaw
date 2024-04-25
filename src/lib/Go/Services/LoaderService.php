<?php
namespace CatPaw\Go\Services;

use function Amp\File\isDirectory;
use function Amp\File\isFile;
use CatPaw\Core\Attributes\Service;

use CatPaw\Core\Directory;
use function CatPaw\Core\error;
use function CatPaw\Core\execute;
use CatPaw\Core\File;
use function CatPaw\Core\goffi;

use CatPaw\Core\GoffiContract;
use function CatPaw\Core\out;

use CatPaw\Core\Unsafe;

#[Service]
class LoaderService {
    /**
     * Load the library using cached binaries (if possible).
     * @template T
     * @param  class-string<T>            $interface     Interface of the shared object.
     * @param  string                     $directoryName
     * @return Unsafe<GoffiContract<T>&T>
     */
    public function load(string $interface, string $directoryName, bool $clear = false):Unsafe {
        if ($clear) {
            File::delete("$directoryName/main.so")->unwrap($error);
            File::delete("$directoryName/main.h")->unwrap($error);
        }

        if (!isDirectory($directoryName)) {
            Directory::create($directoryName)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        if (!isFile("$directoryName/main.go")) {
            return error("Could not initialize library because source file `$directoryName/main.go` does not exist.");
        }

        if (!isFile("$directoryName/main.so") || !isFile("$directoryName/main.h")) {
            execute("go build -o $directoryName/main.so -buildmode=c-shared $directoryName/main.go", out(), $directoryName)->unwrap($error);
            if ($error) {
                return error($error);
            }
            File::delete("$directoryName/main.static.h")->unwrap($error);
        }

        if (!isFile("$directoryName/main.static.h")) {
            execute("cpp -P $directoryName/main.h $directoryName/main.static.h", out(), $directoryName)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        return goffi($interface, "$directoryName/main.so");
    }
}
