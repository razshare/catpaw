<?php

use CatPaw\Attributes\Option;

function main(
    #[Option("--info")] bool $info,
) {
    echo $info?'info':'no info';
    echo PHP_EOL;
}