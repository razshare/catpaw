<?php
use function CatPaw\Core\asFileName;
use function CatPaw\Core\Build\build;
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use CatPaw\Core\Interfaces\EnvironmentInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use function CatPaw\Core\out;
use function CatPaw\Core\Precommit\installPreCommit;
use function CatPaw\Core\Precommit\uninstallPreCommit;
use CatPaw\Core\Result;
use function CatPaw\Text\foreground;
use function CatPaw\Text\nocolor;
use Psr\Log\LoggerInterface;

class TipsCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): void {
        $builder->withOption('t', 'tips', error('no match'));
    }

    public function run(CommandContext $context): void {
            $context->get('t')->unwrap($error);
            if ($error) {
                echo $error.PHP_EOL;
                return;
            }
            $context->accept();

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
    }
}

class InstallPreCommitCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): void {
        $builder->withOption('i', 'install-pre-commit', error('no match'));
    }

    public function run(CommandContext $context): void {
        $command = $context->get('install-pre-commit')->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }
        $context->accept();

        installPreCommit($command)->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }
    }
}

class UninstallPreCommitCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): void {
        $builder->withOption('u', 'uninstall-pre-commit', error('no match'));
    }

    public function run(CommandContext $context): void {
        $context->get('u')->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }
        $context->accept();

        uninstallPreCommit()->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }
    }
}

class HiCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): void {
        $builder->withOption('h', 'hi', error('no match'));
    }

    public function run(CommandContext $context): void {
        $context->get('h')->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }
        $context->accept();
        echo "Hi.\n";
    }
}

class HelpCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): void {}

    public function run(CommandContext $context): void {
        $context->accept();
        echo <<<BASH
            \n
            -b,  --build [-o, --optimize]        Builds the project into a .phar.
            -t,  --tips                          Some tips.
            -h,  --hi                            Says hi. Useful for debugging.
            -i,  --install-pre-commit            Installs the pre-commit hook.
            -u,  --uninstall-pre-commit          Uninstalls the pre-commit hook.
            \n
            BASH;
    }
}


class BuildCommand implements CommandRunnerInterface {
    public function __construct(
        private LoggerInterface $logger,
        private EnvironmentInterface $environment,
    ) {
    }

    public function build(CommandBuilder $builder): void {
        $builder->withOption('b', 'build', error('no match'));
        $builder->withOption('o', 'optimize', ok('0'));
        $builder->withOption('e', 'environment', ok('0'));
    }

    public function run(CommandContext $context): void {
        $context->get('b')->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }
        
        $context->accept();

        $environmentFile = $context->get('e')->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }

        if(!$environmentFile){
            echo "Please point to an environment file using the `--environment` or `-e` options.\n";
            return;
        }

        if(!File::exists(asFileName($environmentFile))){
            echo "File `$environmentFile` doesn't seem to exist.";
            return;
        }

        $this->environment->load($environmentFile)->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }

        $optimize = (bool)$context->get('optimize')->unwrap() or false;

        build($optimize)->unwrap($error);
        if ($error) {
            echo $error.PHP_EOL;
            return;
        }
    }
}


/**
 * 
 * @param  CommandInterface $command
 * @return Result<None>
 */
function main(CommandInterface $command, LoggerInterface $logger, EnvironmentInterface $environment) {
    return 
        $command->register(new BuildCommand($logger, $environment))->unwrap()
        or $command->register(new TipsCommand)->unwrap()
        or $command->register(new HiCommand)->unwrap()
        or $command->register(new InstallPreCommitCommand)->unwrap()
        or $command->register(new UninstallPreCommitCommand)->unwrap()
        or $command->register(new HelpCommand);
}
