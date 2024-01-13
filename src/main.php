<?php

use function CatPaw\Core\anyError;
use CatPaw\Core\Attributes\Option;
use function CatPaw\Core\Build\build;

use function CatPaw\Core\error;
use CatPaw\Core\File;
use function CatPaw\Core\ok;
use function CatPaw\Core\out;
use CatPaw\Core\Unsafe;
use function CatPaw\Text\foreground;

use function CatPaw\Text\nocolor;

/**
 * @return Unsafe<void>
 */
function main(
    // ===> BUILD
    #[Option("--build")]
    bool $build = false,
    #[Option("--build-config-init")]
    bool $buildConfigInit = false,
    #[Option("--build-config")]
    false|string $buildConfig = false,
    #[Option("--build-optimize")]
    bool $buildOptimize = false,

    // ===> TIPS
    #[Option("--tips")]
    bool $tips,
): Unsafe {
    return anyError(fn () => match (true) {
        $build => build(
            buildConfig    : $buildConfig,
            buildConfigInit: $buildConfigInit,
            buildOptimize  : $buildOptimize,
        ),
        $tips => tips(),
    });
}

function tips() {
    try {
        $message = '';

        if (
            File::exists('.git/hooks')
            && !File::exists('.git/hooks/pre-commit')
        ) {
            $message = join([
                foreground(170, 140, 40),
                "Remember to run `",
                foreground(140, 170, 40),
                "composer dev:precommit",
                foreground(170, 140, 40),
                "` if you want to sanitize your code before committing.",
                nocolor(),
                PHP_EOL,
            ]);
        }


        out()->write($message);
        return ok();
    } catch (\Throwable $error) {
        return error($error);
    }
}