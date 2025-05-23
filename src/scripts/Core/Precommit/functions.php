<?php
namespace CatPaw\Core\Precommit;

use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Process;
use CatPaw\Core\Result;

/**
 * Install the git pre-commit hook using the specified command.
 * @return Result<None>
 */
function installPreCommit(string $command):Result {
    if ('' === trim($command)) {
        echo "The `--install-pre-commit` options requires a value.\n";
        echo "The received command was: $command\n";
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
    
    $code = Process::execute("chmod +x $fileName")->unwrap($error);
    if ($error) {
        return error($error);
    }
    
    if (0 !== $code) {
        return error("Could not set the pre-commit hook as executable, the attempt to do so returned a `$code` code.");
    }
    
    echo "Pre-commit hook installed.\n";

    return ok();
}

/**
 * Uninstall the git pre-commit hook.
 * @return Result<None>
 */
function uninstallPreCommit():Result {
    $fileName = '.git/hooks/pre-commit';

    if (!File::exists($fileName)) {
        echo "The pre-commit hook is not installed.\n";
        return ok();
    }

    File::delete($fileName)->unwrap($error);
    if ($error) {
        return error($error);
    }

    echo "Pre-commit hook uninstalled.\n";

    return ok();
}
