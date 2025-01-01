<?php
use CatPaw\Core\Commands\BuildCommand;
use CatPaw\Core\Commands\HelpCommand;
use CatPaw\Core\Commands\InstallPreCommitCommand;
use CatPaw\Core\Commands\UninstallPreCommitCommand;
use function CatPaw\Core\error;
use CatPaw\Core\Errors\NoMatchError;
use CatPaw\Core\Interfaces\CommandRegisterInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;

/**
 * 
 * @param  CommandRegisterInterface  $command
 * @param  BuildCommand              $buildCommand
 * @param  InstallPreCommitCommand   $installPreCommitCommand
 * @param  UninstallPreCommitCommand $uninstallPreCommitCommand
 * @param  HelpCommand               $helpCommand
 * @return Result<None>
 */
function main(
    CommandRegisterInterface $command,
    BuildCommand $buildCommand,
    InstallPreCommitCommand $installPreCommitCommand,
    UninstallPreCommitCommand $uninstallPreCommitCommand,
    HelpCommand $helpCommand
):Result {
    // Build.
    $command->register($buildCommand)->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }

    // Install Pre Commit.
    $command->register($installPreCommitCommand)->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }

    // Uninstall Pre Commit.
    $command->register($uninstallPreCommitCommand)->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }
    
    // Help.
    return $command->register($helpCommand);
}
