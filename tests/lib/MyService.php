<?php

use CatPaw\Attributes\Option;
use CatPaw\Attributes\Service;

#[Service]
class MyService {
    public function __construct(
        #[Option("--info")] private bool $info,
    ) {
        echo $info?'info':'no info';
        echo PHP_EOL;
    }
}