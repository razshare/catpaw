<?php

use function CatPaw\Core\anyError;
use function CatPaw\Core\Build\build;

use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;

use function CatPaw\Core\error;

use CatPaw\Core\File;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use function CatPaw\Core\out;
use function CatPaw\Core\Precommit\installPreCommit;
use function CatPaw\Core\Precommit\uninstallPreCommit;
use CatPaw\Core\Result;
use function CatPaw\Text\foreground;
use function CatPaw\Text\nocolor;

class TipsCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):Result {
        $builder->withOption('t', 'tips', error('no match'));
        return ok();
    }

    public function run(CommandContext $context): Result {
        try {
            $context->get('t')->try();
            $message = '';
    
            if (
                File::exists('.git/hooks')
                && !File::exists('.git/hooks/pre-commit')
            ) {
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
        } catch (Throwable $error) {
            return error($error);
        }
    }
}

class InstallPreCommitCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): Result {
        $builder->withOption('i', 'install-pre-commit', error('no match'));
        return ok();
    }

    public function run(CommandContext $context): Result {
        return anyError(function() use ($context) {
            $command = $context->get('install-pre-commit')->try();
            return installPreCommit($command);
        });
    }
}

class UninstallPreCommitCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): Result {
        $builder->withOption('u', 'uninstall-pre-commit', error('no match'));
        return ok();
    }

    public function run(CommandContext $context): Result {
        return anyError(function() use ($context) {
            $context->get('u')->try();
            return uninstallPreCommit();
        });
    }
}

class BuildCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): Result {
        $builder->withOption('b', 'build', error('no match'));
        $builder->withOption('o', 'optimize', ok('0'));
        return ok();
    }

    public function run(CommandContext $context): Result {
        return anyError(function() use ($context) {
            $context->get('b')->try();
            $optimize = (bool)$context->get('optimize')->try();
            return build($optimize);
        });
    }
}

class HiCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): Result {
        $builder->withOption('h', 'hi', error('no match'));
        return ok();
    }

    public function run(CommandContext $context): Result {
        return anyError(function() use ($context) {
            $context->get('h')->try();
            echo "Hi.\n";
            return ok();
        });
    }
}

class HelpCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):Result {
        return ok();
    }

    public function run(CommandContext $context): Result {
        echo <<<BASH
            \n
            -b,  --build [-o, --optimize]        Builds the project into a .phar.
            -t,  --tips                          Some tips.
            -h,  --hi                            Says hi. Useful for debugging.
            -i,  --install-pre-commit            Installs the pre-commit hook.
            -u,  --uninstall-pre-commit          Uninstalls the pre-commit hook.
            \n
            BASH;
        return ok();
    }
}


/**
 * 
 * @param  CommandInterface $command
 * @return Result<None>
 */
function main(CommandInterface $command) {
    return anyError(fn () => match (true) {
        $command->register(new BuildCommand)->unwrap()              => ok(),
        $command->register(new TipsCommand)->unwrap()               => ok(),
        $command->register(new HiCommand)->unwrap()                 => ok(),
        $command->register(new InstallPreCommitCommand)->unwrap()   => ok(),
        $command->register(new UninstallPreCommitCommand)->unwrap() => ok(),
        default                                                     => $command->register(new HelpCommand)->unwrap()
    });
}
