#!/usr/bin/php
<?php

use CatPaw\Bootstrap;

chdir(getcwd());
require 'vendor/autoload.php';
global $argv;

$filename = str_replace("\\", '/', $argv[1] ?? '');
$watch = 'watch' === ($argv[2] ?? '') || 'dev' === ($argv[2] ?? '');

$watchSleep = !$watch ? 100 : ($argv[3] ?? 100);

Bootstrap::start(
    fileaName: $filename,
    watch: $watch,
    watchSleep: $watchSleep
);
