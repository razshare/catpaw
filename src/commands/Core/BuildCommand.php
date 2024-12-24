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
        $builder->required('b', 'build');
        $builder->optional('e', 'environment');
        $builder->optional('o', 'optimize');
    }

    public function run(CommandContext $context):Result {
        $environment = FileName::create($context->get('environment')?:'build.ini')->absolute();

        if (!File::exists($environment)) {
            return error("File `$environment` doesn't seem to exist.");
        }

        $this->environment->withFileName($environment);
        $this->environment->load()->unwrap($error);
        if ($error) {
            return error($error);
        }

        $optimize = (bool)$context->get('optimize');
        
        build($optimize)->unwrap($error);
        if ($error) {
            return error($error);
        }

        return ok();
    }
}