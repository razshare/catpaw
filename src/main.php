<?php

use Amp\ByteStream\ClosedException;
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
 * @param  bool            $tips
 * @param  bool            $hi
 * @param  bool            $build
 * @param  bool            $buildOptimize
 * @throws ClosedException
 * @return Unsafe<void>
 */
function main(
    // ===> TIPS
    #[Option("--tips")]
    bool $tips,

    // ===> Hi
    #[Option("--hi")]
    bool $hi,

    // ===> BUILD
    #[Option("--build")]
    bool $build = false,
    #[Option("--build-optimize")]
    bool $buildOptimize = false,
): Unsafe {
    return anyError(fn () => match (true) {
        $build  => build(buildOptimize:$buildOptimize),
        $tips   => tips(),
        $hi     => out()->write("hello\n"),
        default => true,
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
