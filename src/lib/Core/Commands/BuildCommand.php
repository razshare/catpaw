<?php
namespace CatPaw\Core\Commands;

use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\Build\build;
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\FileName;
use CatPaw\Core\Interfaces\CommandInterface;
use CatPaw\Core\Interfaces\EnvironmentInterface;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;

#[Provider]
final class BuildCommand implements CommandInterface {
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