<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;

readonly class ApplicationBundled implements CommandRunnerInterface {
    public function __construct(
        public string $main,
        public string $name,
        public string $libraries,
        public string $resources,
        public string $environment,
    ) {
    }

    public function build(CommandBuilder $builder): Result {
        // Options.
        $builder->withOption('e', 'environment', ok('env.ini'));
        return ok();
    }

    public function run(CommandContext $context): Result {
        return anyError(function() use ($context) {
            // Options.
            $environment = $context->get('environment')->try();
            if (!$environment) {
                $environment = $this->environment;
            }
            Bootstrap::start(
                main: $this->main,
                name: $this->name,
                libraries: $this->libraries,
                resources: $this->resources,
                environment: $environment,
                dieOnChange: false
            );
            return ok();
        });
    }
}