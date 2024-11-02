<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;
readonly class Application implements CommandRunnerInterface {
    public function __construct(private string $startFileName) {
    }

    public function build(CommandBuilder $builder):void {
        $builder->withOption('m', 'main', error('No value provided.'));
        $builder->withOption('d', "die-on-change", ok('0'));
        $builder->withOption('w', "watch", ok('0'));
        $builder->withOption('p', 'php', ok('php'));
        $builder->withOption('n', 'name', ok('App'));
        $builder->withOption('l', 'libraries', ok('0'));
        $builder->withOption('r', 'resources', ok('0'));
        $builder->withOption('e', 'environment', ok('0'));

        $builder->requires('m');
    }

    public function run(CommandContext $context):Result {
        global $argv;

        $dieOnChange = $context->get('die-on-change')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $watch = $context->get('watch')->unwrap($error);
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

        $libraries = $context->get('libraries')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $resources = $context->get('resources')->unwrap($error);
        if ($error) {
            return error($error);
        }

        $environment = $context->get('environment')->unwrap($error);
        if ($error) {
            return error($error);
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
                dieOnChange: match ($dieOnChange) {
                    '1'     => true,
                    default => false,
                },
            );
        }
        return ok();
    }
}