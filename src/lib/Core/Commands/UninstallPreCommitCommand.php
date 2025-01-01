<?php
namespace CatPaw\Core\Commands;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\CommandInterface;
use function CatPaw\Core\ok;
use function CatPaw\Core\Precommit\uninstallPreCommit;
use CatPaw\Core\Result;

#[Provider]
final class UninstallPreCommitCommand implements CommandInterface {
    public function build(CommandBuilder $builder):void {
        $builder->required('u', 'uninstall-pre-commit');
    }

    public function run(CommandContext $context):Result {
        uninstallPreCommit()->unwrap($error);
        if ($error) {
            return error($error);
        }

        return ok();
    }
}