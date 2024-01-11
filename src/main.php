<?php
use function CatPaw\Core\anyError;
use CatPaw\Core\Attributes\Option;

use function CatPaw\Core\Build\build;
use CatPaw\Core\Unsafe;

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