<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;

readonly class ApplicationBundled implements CommandRunnerInterface {
    /**
     * 
     * @param  string $main
     * @param  string $name
     * @param  string $libraries
     * @param  string $resources
     * @param  string $environment
     * @return void
     */
    public function __construct(
        public string $main,
        public string $name,
        public string $libraries,
        public string $resources,
        public string $environment,
    ) {
    }

    public function build(CommandBuilder $builder):void {
        $builder->optional('e', 'environment');
    }

    public function run(CommandContext $context):Result {
        $environment = $context->get('environment')?:$this->environment;

        $libraries = explode(',', $this->libraries);
        $resources = explode(',', $this->resources);


        foreach ($libraries as $key => &$library) {
            if ('' === $library) {
                unset($libraries[$key]);
                continue;
            }

            $libraryReal = (string)FileName::create($library)->absolute();

            if (!$libraryReal) {
                return error("Trying to find php library `$library`, but the directory doesn't seem to exist.");
            }

            $library = $libraryReal;
        }
        
        foreach ($resources as $key => &$resource) {
            if ('' === $resource) {
                unset($resources[$key]);
                continue;
            }
            $resource = realpath($resource);
        }

        Bootstrap::start(
            main: $this->main,
            name: $this->name,
            libraries: $libraries,
            resources: $resources,
            environment: $environment,
            dieOnChange: false
        );

        return ok();
    }
}