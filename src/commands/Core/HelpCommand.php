<?php
namespace CatPaw\Core;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Interfaces\CommandRunnerInterface;

#[Provider]
class HelpCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
    }

    public function run(CommandContext $context):Result {
        echo <<<BASH
            \n
            -b,  --build [-o, --optimize]        Builds the project into a .phar.
            -w,  --web [-p, --project]           Dump web server requirements into a project directory.
            -t,  --tips                          Some tips.
            -h,  --hi                            Says hi. Useful for debugging.
            -i,  --install-pre-commit            Installs the pre-commit hook.
            -u,  --uninstall-pre-commit          Uninstalls the pre-commit hook.
            \n
            BASH;
        return ok();
    }
}