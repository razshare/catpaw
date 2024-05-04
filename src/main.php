<?php
use Amp\ByteStream\ClosedException;

use function CatPaw\Core\anyError;
use CatPaw\Core\Attributes\Option;
use function CatPaw\Core\Build\build;
use CatPaw\Core\Container;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use function CatPaw\Core\out;
use function CatPaw\Core\Precommit\installPreCommit as installPreCommit;

use CatPaw\Core\Unsafe;
use CatPaw\Cui\Contracts\CuiContract;
use CatPaw\Cui\Services\CuiService;
use function CatPaw\Text\foreground;
use function CatPaw\Text\nocolor;

/**
 * @param  bool            $tips
 * @param  bool            $hi
 * @param  bool            $build
 * @param  bool            $buildOptimize
 * @throws ClosedException
 * @return Unsafe<None>
 */
function main(
    // ===> PRE-COMMIT-USING
    #[Option("--install-pre-commit")]
    string $installPreCommit = '',

    // ===> TIPS
    #[Option("--tips")]
    bool $tips = false,

    // ===> Hi
    #[Option("--hi")]
    bool $hi = false,

    // ===> BUILD
    #[Option("--build")]
    bool $build = false,
    #[Option("--build-optimize")]
    bool $buildOptimize = false,
): Unsafe {
    return anyError(fn () => match (true) {
        $build                  => build(buildOptimize:$buildOptimize),
        $tips                   => tips(),
        $hi                     => hi(),
        (bool)$installPreCommit => installPreCommit($installPreCommit),
        default                 => print("No valid options provided."),
    });
}

/**
 *
 * @return Unsafe<None>
 */
function hi():Unsafe {
    $cui = Container::create(CuiService::class)->unwrap($error);
    if ($error) {
        return error($error);
    }

    $cui->load()->unwrap($error);
    if ($error) {
        return error($error);
    }

    $cui->loop(function(CuiContract $lib) {
        $maxX = $lib->MaxX();
        $maxY = $lib->MaxY();

        $message = "hello";
        $len     = strlen($message);

        $x0 = ($maxX / 2) - ($len / 2);
        $y0 = ($maxY / 2) - 1;
        $x1 = ($maxX / 2) + ($len / 2) + 1;
        $y1 = ($maxY / 2) + 1;

        $view = $lib->NewView("main", (int)$x0, (int)$y0, (int)$x1, (int)$y1);
        $lib->Fprintln($view, $message);
    });


    return ok();
}

/**
 *
 * @return Unsafe<None>
 */
function tips():Unsafe {
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
                "php catpaw.phar --install-pre-commit='your pre-commit command goes here.'",
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
