<?php


use function CatPaw\anyError;

function main() {
    return anyError(
        \CatPaw\Build\main(initConfig:false, config:'./build.yml'),
    );
}