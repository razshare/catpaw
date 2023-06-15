<?php

use CatPaw\Attributes\Arguments;
use CatPaw\Attributes\Option;

function main(
    #[Arguments] array $args,
    #[Option("--entry")] string $entry
) {
    echo "entry:$entry\n";
    print_r($args);
}