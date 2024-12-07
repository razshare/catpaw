<?php
namespace CatPaw\Core;

use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\Build\build;
use CatPaw\Core\Interfaces\CommandRunnerInterface;
use CatPaw\Core\Interfaces\EnvironmentInterface;

#[Provider]
final class BuildCommand implements CommandRunnerInterface {
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