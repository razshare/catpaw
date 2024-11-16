<?php
// src/api/get.php
use CatPaw\Web\Attributes\ProducesItem;
use function CatPaw\Web\success;

return
    #[ProducesItem(200, 'text/plain', 'Success!', string::class)]
    fn () => success('hello')->item();