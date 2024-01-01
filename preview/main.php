<?php

use CatPaw\Services\EnvironmentService;

function main(EnvironmentService $env) {
    $env->setFileName("./build.yml");
    $load = $env->load();

    if ($load->error) {
        return $load;
    }

    echo $_ENV['libraries'];
}