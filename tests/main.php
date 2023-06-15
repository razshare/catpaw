<?php

use CatPaw\Attributes\Option;

function main(
    #[Option("--entry")] string $entry
) {
    echo "entry:\n";
}