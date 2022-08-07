<?php

namespace {

    use function Amp\File\openFile;

    use CatPaw\Amp\File\CatPawFile;
    
    // benchmarks
    function main() {
        /** @var CatPawFile */
        $file  = yield openFile("output.txt", "w+");
        $chunk = str_repeat('a', 1_000);
        for ($i = 0; $i < 1_000_000; $i++) {
            yield $file->write($chunk);
        }
        yield $file->close();
    }
}