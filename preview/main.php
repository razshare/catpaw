<?php

use CatPaw\Services\EnvironmentService;

function main(EnvironmentService $env) {
    $env->setFileName("./build.yml");
    $load = $env->load();

    if ($load->error) {
        return $load;
    }
}