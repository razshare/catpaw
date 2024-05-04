<?php
namespace CatPaw\Core\Precommit;

use function CatPaw\Core\error;
use CatPaw\Core\File;

use CatPaw\Core\None;
use function CatPaw\Core\ok;

use CatPaw\Core\Unsafe;

/**
 * Install the git pre-commit hook using the specified command.
 * @return Unsafe<None>
 */
function installPreCommit(string $command):Unsafe {
    if ('' === trim($command)) {
        echo "The `--install-pre-commit` options requires a value.\n";
        echo "The value should be whatever command you want to execute before the commit, for example: php catpaw.phar --install-pre-commit=\"echo 'this is a message before the commit.'\"\n";
        return ok();
    }

    $fileName = '.git/hooks/pre-commit';

    $file = File::open($fileName, 'w+')->unwrap($error);
    if ($error) {
        return error($error);
    }

    $file->write(
        <<<BASH
            #!/usr/bin/env bash
            $command
            BASH
    )->unwrap($error);
    if ($error) {
        return error($error);
    }

    echo "Installed pre-commit hook.\n";

    return ok();
}

/**
 * Uninstall the git pre-commit hook.
 * @return Unsafe<None>
 */
function uninstallPreCommit():Unsafe {
    $fileName = '.git/hooks/pre-commit';

    if (!File::exists($fileName)) {
        echo "The pre-commit hook is not installed.\n";
        return ok();
    }

    File::delete($fileName)->unwrap($error);
    if ($error) {
        return error($error);
    }

    echo "Uninstalled pre-commit hook.\n";

    return ok();
}
