<?php

use function Amp\async;
use function Amp\delay;

use function Amp\Future\awaitAll;
use CatPaw\Attributes\Arguments;
use CatPaw\Attributes\Option;

function main(
    #[Arguments] array $args,
    #[Option("--entry")] string $entry
) {
    echo "entry:$entry\n";
    print_r($args);

    $fiber1 = async(function() {
        delay(1);
        echo "hello...";
    });

    $fiber2 = async(function() {
        delay(2);
        echo "world\n";
    });



    awaitAll([$fiber1,$fiber2]);
}