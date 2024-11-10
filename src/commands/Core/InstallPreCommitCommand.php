<?php
namespace CatPaw\Core;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use function CatPaw\Core\Precommit\installPreCommit;

#[Provider]
final class InstallPreCommitCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
        $builder->withOption('i', 'install-pre-commit', error('No value provided.'));
        $builder->requires('i');
    }

    public function run(CommandContext $context):Result {
        $command = $context->get('install-pre-commit')->unwrap($error);
        if ($error) {
            return error($error);
        }

        installPreCommit($command)->unwrap($error);
        if ($error) {
            return error($error);
        }

        return ok();
    }
}