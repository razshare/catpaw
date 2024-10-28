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

    public function build(CommandBuilder $builder): void {
        $builder->withOption('e', 'environment', error('No value provided.'));
        $builder->requires('e');
    }

    public function run(CommandContext $context): Result {
        $environment = $context->get('environment')->unwrap($error);
        if ($error) {
            return error($error);
        }

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
    }
}