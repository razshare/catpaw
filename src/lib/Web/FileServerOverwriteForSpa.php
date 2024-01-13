<?php
namespace CatPaw\Web;

use function Amp\File\isDirectory;
use CatPaw\Core\File;
use CatPaw\Web\Interfaces\FileServerOverwriteInterface;

readonly class FileServerOverwriteForSpa implements FileServerOverwriteInterface {
    public static function create(Server $server):self {
        return new self($server);
    }

    private function __construct(private Server $server) {
    }

    public function overwrite(string $fileName, string $path): string {
        if (str_starts_with($path, $this->server->apiPrefix)) {
            return $fileName;
        }
        // Required for Spa mode
        if (isDirectory($fileName) || !File::exists($fileName)) {
            return "{$this->server->www}/index.html";
        }
        return $fileName;
    }
}