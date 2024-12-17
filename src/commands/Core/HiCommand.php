<?php
namespace CatPaw\Core;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Interfaces\CommandRunnerInterface;

#[Provider]
final class HiCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
        $builder->required('h', 'hi');
    }

    public function run(CommandContext $context):Result {
        return ok();
    }
}