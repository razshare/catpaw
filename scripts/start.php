#!/usr/bin/php
<?php

use CatPaw\Bootstrap;

chdir(getcwd());
require 'vendor/autoload.php';
global $argv;

$filename = $argv[1]??'';
$dev = 'dev' === ($argv[2]??'');
$devSleep = !$dev ? 100 : ($argv[3]??100);

Bootstrap::start(
	filename: $filename,
	dev     : $dev,
	devSleep: $devSleep
);