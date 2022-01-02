#!/usr/bin/php
<?php
chdir(getcwd());
require 'vendor/autoload.php';
global $argv;

$filename = $argv[1]??'';
$dev = 'dev' === ($argv[2]??'');
$devSleep = !$dev ? 100 : ($argv[3]??100);

\CatPaw\Tools\Bootstrap::start(
	filename: $filename,
	dev     : $dev,
	devSleep: $devSleep
);