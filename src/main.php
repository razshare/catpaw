<?php

use CatPaw\Core\Attributes\Option;
use CatPaw\Core\Unsafe;
use function CatPaw\Core\anyError;
use function CatPaw\Core\Build\build;

/**
 * @return Unsafe<void>
 */
function main(
    #[Option("--build")]
    bool         $build = false,
    #[Option("--build-config-init")]
    bool         $buildConfigInit = false,
    #[Option("--build-config")]
    false|string $buildConfig = false,
    #[Option("--build-optimize")]
    bool         $buildOptimize = false,
): Unsafe {
    return anyError(fn() => match (true) {
        $build => build(
            buildConfig    : $buildConfig,
            buildConfigInit: $buildConfigInit,
            buildOptimize  : $buildOptimize,
        )
    });
}