<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;
readonly class Application implements CommandRunnerInterface {
    public function __construct(private string $startFileName) {
    }

    public function build(CommandBuilder $builder):void {
        $builder->required('m', 'main');
        $builder->optional('p', 'php');
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
        $php         = $context->get('php')?:'/usr/bin/php';
        $name        = $context->get('name')?:'App';
        $main        = $context->get('main')?:'';
        $libraries   = $context->get('libraries')?:'';
        $resources   = explode(',', $context->get('resources')?:'');
        $environment = $context->get('environment')?:'';

        if ($main) {
            $main = realpath($main);
        }
        
        if ($libraries) {
            $libraries = realpath($libraries);
        }
        
        if ($resources) {
            foreach ($resources as &$resource) {
                $resource = realpath($resource);
            }
        }
        $resources = join(',', $resources);
        
        if ($environment) {
            $environment = realpath($environment);
        }


        if (!$main) {
            return error('No main file specified. Use `--main=src/main.php` to specify a main file.');
        }

        if ($watch) {
            $arguments   = array_filter(array_slice($argv, 1), fn ($option) => trim($option) !== '--watch' && trim($option) !== '-w');
            $arguments[] = '--die-on-change';
            Bootstrap::spawn(
                binary: $php,
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