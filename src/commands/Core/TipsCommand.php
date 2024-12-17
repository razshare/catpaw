<?php
namespace CatPaw\Core;

use CatPaw\Core\Attributes\Provider;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use function CatPaw\Text\foreground;
use function CatPaw\Text\nocolor;

#[Provider]
final class TipsCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
        $builder->required('t', 'tips');
    }

    public function run(CommandContext $context):Result {
        $message = '';
    
        if (File::exists('.git/hooks') && !File::exists('.git/hooks/pre-commit')) {
            $message = join([
                foreground(170, 140, 40),
                "Remember to run `",
                foreground(140, 170, 40),
                "php catpaw.phar --install-pre-commit='your pre-commit command goes here.'",
                foreground(170, 140, 40),
                "` if you want to sanitize your code before committing.",
                nocolor(),
                PHP_EOL,
            ]);
        }
    
        out()->write($message?:"No more tips.\n");

        return ok();
    }
}