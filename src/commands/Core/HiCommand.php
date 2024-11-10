<?php
namespace CatPaw\Core;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Interfaces\CommandRunnerInterface;

#[Provider]
class HiCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
        $builder->withOption('h', 'hi', error('No value provided.'));
        $builder->requires('h');
    }

    public function run(CommandContext $context):Result {
        echo "Hi.\n";
        return ok();
    }
}