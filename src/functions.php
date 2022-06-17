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
            $isDir = yield isDirectory("$path/$item");
            if ($isDir) {
                foreach (yield listFilesRecursive("$path/$item") as $subItem) {
                    $files[] = $subItem;
                }
                continue;
            }

            $files[] = "$path/$item";
        }
        return $files;
    });
}