<?php

namespace CatPaw;

use Closure;
use JetBrains\PhpStorm\ArrayShape;
use Monolog\Logger;

abstract class MainConfiguration {

    /** @var false|Logger Application logger. */
    public false|Logger $logger = false;

    /** @var false|Closure Will be executed just before the server starts. */
    public false|Closure $beforeStart = false;

    #[ArrayShape([
        "enabled" => "bool",
        "sleep" => "int",
    ])]
    public array $watch = [
        "enabled" => false,
        "sleep" => 100,
    ];
}
