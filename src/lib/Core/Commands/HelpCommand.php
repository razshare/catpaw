<?php
namespace CatPaw\Core\Commands;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use CatPaw\Core\Interfaces\CommandInterface;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;

#[Provider]
final class HelpCommand implements CommandInterface {
    public function build(CommandBuilder $builder):void {
    }

    public function run(CommandContext $context):Result {
        echo <<<BASH
            -b,  --build [-o, --optimize]        Builds the project into a .phar.
            -w,  --web [-p, --project]           Dump web server requirements into a project directory.
            -i,  --install-pre-commit            Installs the pre-commit hook.
            -u,  --uninstall-pre-commit          Uninstalls the pre-commit hook.
            
            BASH;
        return ok();
    }
}