<?php
namespace CatPaw\Core;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use function CatPaw\Core\Precommit\uninstallPreCommit;

#[Provider]
class UninstallPreCommitCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
        $builder->withOption('u', 'uninstall-pre-commit', error('No value provided.'));
        $builder->requires('u');
    }

    public function run(CommandContext $context):Result {
        uninstallPreCommit()->unwrap($error);
        if ($error) {
            return error($error);
        }

        return ok();
    }
}