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
        // Options.
        $builder->withOption('e', 'environment', ok('env.ini'));
    }

    public function run(CommandContext $context): void {
            // Options.
            $environment = $context->get('environment')->unwrap($error);
            if ($error) {
                echo $error.PHP_EOL;
                return;
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
    }
}