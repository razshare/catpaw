<?php
namespace CatPaw\Core\Commands;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use function CatPaw\Core\error;
use CatPaw\Core\Interfaces\CommandInterface;
use function CatPaw\Core\ok;
use function CatPaw\Core\Precommit\installPreCommit;
use CatPaw\Core\Result;

#[Provider]
final class InstallPreCommitCommand implements CommandInterface {
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