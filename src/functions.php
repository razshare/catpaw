<?php
namespace CatPaw;

use function Amp\call;
use function Amp\File\isDirectory;
use function Amp\File\listFiles;

use Amp\Promise;

function listFilesRecursive(string $path):Promise {
    return call(function() use ($path) {
        $items = yield listFiles($path);
        $files = [];
        foreach ($items as $item) {
            $filename = "$path/$item";
            $isDir    = yield isDirectory($filename);
            if ($isDir) {
                foreach (yield listFilesRecursive($filename) as $subItem) {
                    $files[] = $subItem;
                }
                continue;
            }

            $files[] = $filename;
        }
        return $files;
    });
}