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
            if (yield isDirectory($item)) {
                foreach (yield listFilesRecursive($item) as $subItem) {
                    $files[] = $subItem;
                }
                continue;
            }

            $files[] = $item;
        }
        return $files;
    });
}