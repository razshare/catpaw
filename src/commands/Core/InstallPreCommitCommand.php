<?php
namespace CatPaw\Core;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use function CatPaw\Core\Precommit\installPreCommit;

#[Provider]
final class InstallPreCommitCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
        $builder->required('i', 'install-pre-commit');
    }

    public function run(CommandContext $context):Result {
        $command = $context->get('install-pre-commit')?:'';

        installPreCommit($command)->unwrap($error);
        if ($error) {
            return error($error);
        }

        return ok();
    }
}