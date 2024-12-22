<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;
readonly class Application implements CommandRunnerInterface {
    public function __construct(private string $startFileName) {
    }

    public function build(CommandBuilder $builder):void {
        $builder->optional('m', 'main');
        $builder->optional('p', 'php');
        $builder->optional('s', 'spawner');
        $builder->optional('e', 'environment');
        $builder->optional('n', 'name');
        $builder->optional('d', "die-on-change");
        $builder->optional('w', "watch");
        $builder->optional('l', 'libraries');
        $builder->optional('r', 'resources');
    }

    public function run(CommandContext $context):Result {
        global $argv;

        $dieOnChange = (bool)$context->get('die-on-change');
        $watch       = (bool)$context->get('watch');
        $spawner     = $context->get('spawner')?:$context->get('php')?:'/usr/bin/php';
        $name        = $context->get('name')?:'App';
        $main        = $context->get('main')?:'';
        $libraries   = explode(',', $context->get('libraries')?:'');
        $resources   = explode(',', $context->get('resources')?:'');
        $environment = $context->get('environment')?:'';

        if ($main) {
            $main = realpath($main);
        }

        foreach ($libraries as $key => &$library) {
            if ('' === $library) {
                unset($libraries[$key]);
                continue;
            }
            $library = realpath($library);
        }
        
        foreach ($resources as $key => &$resource) {
            if ('' === $resource) {
                unset($resources[$key]);
                continue;
            }
            $resource = realpath($resource);
        }
        
        if ($environment) {
            $environment = realpath($environment);
        }

        if ($watch) {
            $arguments   = array_filter(array_slice($argv, 1), fn ($option) => trim($option) !== '--watch' && trim($option) !== '-w');
            $arguments[] = '--die-on-change';
            Bootstrap::spawn(
                command: $spawner,
                fileName: $this->startFileName,
                arguments: $arguments,
                main: $main,
                libraries: $libraries,
                resources: $resources,
            );
        } else {
            Bootstrap::start(
                main: $main,
                name: $name,
                libraries: $libraries,
                resources: $resources,
                environment: $environment,
                dieOnChange: $dieOnChange,
            );
        }
        return ok();
    }
}