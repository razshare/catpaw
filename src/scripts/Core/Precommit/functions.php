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