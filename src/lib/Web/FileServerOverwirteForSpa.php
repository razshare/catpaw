<?php
namespace CatPaw\Web;

use function Amp\File\isDirectory;
use CatPaw\File;
use CatPaw\Web\Interfaces\FileServerOverwriteInterface;

class FileServerOverwirteForSpa implements FileServerOverwriteInterface {
    public static function create(Server $server):self {
        return new self($server);
    }

    private function __construct(private Server $server) {
    }

    public function overwrite(string $fileName): string {
        // Required for Spa mode
        if (isDirectory($fileName) || !File::exists($fileName)) {
            return "$this->server->www/index.html";
        }
        return $fileName;
    }
}