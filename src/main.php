<?php
use function CatPaw\anyError;
use CatPaw\Attributes\Option;

use function CatPaw\Build\build;
use CatPaw\Unsafe;

/**
 * @return Unsafe<void>
 */
function main(
    #[Option("--build")]
    bool $build = false,
    #[Option("--build-config-init")]
    bool $buildConfigInit = false,
    #[Option("--build-config")]
    false|string $buildConfig = false,
    #[Option("--build-optimize")]
    bool $buildOptimize = false,
):Unsafe {
    return anyError(
        match (true) {
            $build => build(
                buildConfig: $buildConfig,
                buildConfigInit: $buildConfigInit,
                buildOptimize: $buildOptimize,
            ),
        }
    );
}