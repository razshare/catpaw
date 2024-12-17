<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;
readonly class Application implements CommandRunnerInterface {
    public function __construct(private string $startFileName) {
    }

    public function build(CommandBuilder $builder):void {
        $builder->withOption('m', 'main', error('No value provided.'));
        $builder->withOption('p', 'php', ok('/usr/bin/php'));
        $builder->withOption('e', 'environment', ok('env.ini'));
        $builder->withOption('d', "die-on-change", ok(''));
        $builder->withOption('w', "watch", ok(''));
        $builder->withOption('n', 'name', ok('App'));
        $builder->withOption('l', 'libraries', ok(''));
        $builder->withOption('r', 'resources', ok(''));

        $builder->requires('m');
    }

    public function run(CommandContext $context):Result {
        global $argv;

        $dieOnChange = (bool)$context->get('die-on-change')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $watch = (bool)$context->get('watch')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $php = $context->get('php')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $name = $context->get('name')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $main = $context->get('main')->unwrap($error);
        if ($error) {
            return error($error);
        }
        if ($main) {
            $mainReal = realpath($main)?:'';
            if (!$mainReal) {
                return error("Could not find main file `$main`.");
            }
            $main = $mainReal;
        }

        $libraries = $context->get('libraries')->unwrap($error);
        if ($error) {
            return error($error);
        }
        if ($libraries) {
            $librariesReal = realpath($libraries)?:'';
            if (!$librariesReal) {
                return error("Could not find libraries directory `$libraries`.");
            }
            $libraries = $librariesReal;
        }

        $resources = $context->get('resources')->unwrap($error);
        if ($error) {
            return error($error);
        }
        if ($resources) {
            $resourcesReal = realpath($resources)?:'';
            if (!$resourcesReal) {
                return error("Could not find libraries directory `$resources`.");
            }
            $resources = $resourcesReal;
        }

        $environment = $context->get('environment')->unwrap($error);
        if ($error) {
            return error($error);
        }
        if ($environment) {
            $environmentReal = realpath($environment)?:'';
            if (!$environmentReal) {
                return error("Could not find environment file `$environment`.");
            }
            $environment = $environmentReal;
        }

        if ('' === $main) {
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