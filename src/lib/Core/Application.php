<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;
readonly class Application implements CommandRunnerInterface {
    public function __construct(private string $startFileName) {
    }

    public function build(CommandBuilder $builder):void {
        $builder->withOption('m', 'main', error('No value provided.'));
        $builder->withOption('p', 'php');
        $builder->withOption('e', 'environment');
        $builder->withOption('n', 'name');
        $builder->withOption('d', "die-on-change");
        $builder->withOption('w', "watch");
        $builder->withOption('l', 'libraries');
        $builder->withOption('r', 'resources');

        $builder->requires('m');
    }

    public function run(CommandContext $context):Result {
        global $argv;

        $dieOnChange = (bool)$context->get('die-on-change');
        $watch       = (bool)$context->get('watch');
        $php         = $context->get('php')?:'/usr/bin/php';
        $name        = $context->get('name')?:'App';
        $main        = $context->get('main')?:'';
        $libraries   = $context->get('libraries')?:'';
        $resources   = $context->get('resources')?:'';
        $environment = $context->get('environment')?:'env.ini';

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