<?php

use function CatPaw\Core\anyError;
use function CatPaw\Core\Build\build;

use CatPaw\Core\Command;

use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use function CatPaw\Core\out;
use function CatPaw\Core\Precommit\installPreCommit;
use function CatPaw\Core\Precommit\uninstallPreCommit;

use CatPaw\Core\Unsafe;
use function CatPaw\Text\foreground;
use function CatPaw\Text\nocolor;

/**
 * 
 * @return Unsafe<None>
 */
function main(): Unsafe {
    return anyError(fn () => match (true) {
        Command::create('--build --optimize', build(...))->try()                  => ok(),
        Command::create('--tips', tips(...))->try()                               => ok(),
        Command::create('--install-pre-commit', installPreCommit(...))->try()     => ok(),
        Command::create('--uninstall-pre-commit', uninstallPreCommit(...))->try() => ok(),
        default                                                                   => ok(print(<<<BASH
            \n
            --build [--optimize]            Builds the project into a .phar.
            --tips                          Some tips.
            --hi                            Says hi. Useful for debugging.
            --install-pre-commit            Installs the pre-commit hook.
            --uninstall-pre-commit          Uninstalls the pre-commit hook.
            \n
            BASH)),
    });
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

        out()->write($message?:"No more tips.\n");
        return ok();
    } catch (\Throwable $error) {
        return error($error);
    }
}
