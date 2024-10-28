<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;
readonly class Application implements CommandRunnerInterface {
    public function __construct(private string $startFileName) {
    }

    public function build(CommandBuilder $builder): void {
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

    public function run(CommandContext $context): Result {
        global $argv;
        return anyError(function() use ($context, $argv) {
            $dieOnChange = $context->get('die-on-change')->try();
            $watch       = $context->get('watch')->try();
            $php         = $context->get('php')->try();
            $name        = $context->get('name')->try();
            $main        = $context->get('main')->try();
            $libraries   = $context->get('libraries')->try();
            $resources   = $context->get('resources')->try();
            $environment = $context->get('environment')->try();


            if ('' === $main) {
                echo 'No main file specified. Use `--main=src/main.php` to specify a main file.'.PHP_EOL;
                return;
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
        });
    }
}