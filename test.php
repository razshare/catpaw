<?php

namespace {

    use Amp\File\File;
    use function Amp\File\openFile;

    use CatPaw\Utilities\Stream;

    function main() {
        // $file = Stream::of(fopen("output.txt", "w+"));
        
        /** @var File */
        $file = yield openFile("output.txt", "w+");

        $chunk = str_repeat('a', 1_000);
        for ($i = 0; $i < 1_000_000; $i++) {
            yield $file->write($chunk);
        }
        yield $file->close();
    }
}