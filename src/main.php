<?php

use function CatPaw\Core\asFileName;
use function CatPaw\Core\Build\build;
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\Implementations\Command\NoMatchError;
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

class TipsCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
        $builder->withOption('t', 'tips', error('No value provided.'));
        $builder->requires('t');
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

class InstallPreCommitCommand implements CommandRunnerInterface {
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

class UninstallPreCommitCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
        $builder->withOption('u', 'uninstall-pre-commit', error('No value provided.'));
        $builder->requires('u');
    }

    public function run(CommandContext $context):Result {
        uninstallPreCommit()->unwrap($error);
        if ($error) {
            return error($error);
        }

        return ok();
    }
}

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

class HelpCommand implements CommandRunnerInterface {
    public function build(CommandBuilder $builder):void {
    }

    public function run(CommandContext $context):Result {
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


class BuildCommand implements CommandRunnerInterface {
    public function __construct(private EnvironmentInterface $environment) {
    }

    public function build(CommandBuilder $builder):void {
        $builder->withOption('b', 'build', error('No value provided.'));
        $builder->withOption('o', 'optimize', ok('0'));
        $builder->withOption('e', 'environment', ok('0'));

        $builder->requires('b');
        $builder->requires('e');
    }

    public function run(CommandContext $context):Result {
        $environmentFile = $context->get('e')->unwrap($error);
        if ($error) {
            return error($error);
        }

        if (!$environmentFile) {
            return error("Please point to an environment file using the `--environment` or `-e` options.\n");
        }

        if (!File::exists(asFileName($environmentFile))) {
            return error("File `$environmentFile` doesn't seem to exist.");
        }

        $this->environment->withFileName($environmentFile);
        $this->environment->load()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $optimize = (bool)$context->get('optimize')->unwrap() or false;

        build($optimize)->unwrap($error);
        if ($error) {
            return error($error);
        }

        return ok();
    }
}


/**
 * 
 * @param  CommandInterface     $command
 * @param  EnvironmentInterface $environment
 * @return Result<None>
 */
function main(CommandInterface $command, EnvironmentInterface $environment):Result {
    // Build.
    $command->register(new BuildCommand($environment))->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }
    
    // Tips.
    $command->register(new TipsCommand)->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }
    
    // Hi.
    $command->register(new HiCommand)->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }

    // Install Pre Commit.
    $command->register(new InstallPreCommitCommand)->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }

    // Uninstall Pre Commit.
    $command->register(new UninstallPreCommitCommand)->unwrap($error);
    if ($error) {
        if (NoMatchError::class !== $error::class) {
            return error($error);
        }
    } else {
        return ok();
    }

    // Help.
    return $command->register(new HelpCommand);
}
