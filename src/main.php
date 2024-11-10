<?php
use CatPaw\Core\BuildCommand;
use function CatPaw\Core\error;
use CatPaw\Core\HelpCommand;
use CatPaw\Core\HiCommand;
use CatPaw\Core\Implementations\Command\NoMatchError;
use CatPaw\Core\InstallPreCommitCommand;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use CatPaw\Core\TipsCommand;
use CatPaw\Core\UninstallPreCommitCommand;

/**
 * 
 * @param  CommandInterface          $command
 * @param  BuildCommand              $buildCommand
 * @param  TipsCommand               $tipsCommand
 * @param  HiCommand                 $hiCommand
 * @param  InstallPreCommitCommand   $installPreCommitCommand
 * @param  UninstallPreCommitCommand $uninstallPreCommitCommand
 * @param  HelpCommand               $helpCommand
 * @return Result<None>
 */
function main(
    CommandInterface $command,
    BuildCommand $buildCommand,
    TipsCommand $tipsCommand,
    HiCommand $hiCommand,
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
    
    // Tips.
    $command->register($tipsCommand)->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }
    
    // Hi.
    $command->register($hiCommand)->unwrap($error);
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
