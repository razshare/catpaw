<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;

readonly class Application implements CommandRunnerInterface {
    public function __construct(public string $startFileName) {
    }

    public function build(CommandBuilder $builder): Result {
        // Flags.
        $builder->withRequiredOption('d', "die-on-change");
        $builder->withRequiredOption('w', "watch");

        // Options.
        $builder->withOption('p', 'php', ok('php'));
        $builder->withOption('n', 'name', ok('App'));
        $builder->withOption('m', 'main', error('Missing main file.'));
        $builder->withOption('l', 'libraries');
        $builder->withOption('r', 'resources');
        $builder->withOption('e', 'environment');

        return ok();
    }

    public function run(CommandContext $context): Result {
        return anyError(function() use ($context) {
            // Required.
            $dieOnChange = $context->get('die-on-change')->unwrap() or false;
            $watch       = $context->get('watch')->unwrap()         or false;

            // Optionals.
            $php         = $context->get('php')->try();
            $name        = $context->get('name')->try();
            $main        = $context->get('main')->try();
            $libraries   = $context->get('libraries')->try();
            $resources   = $context->get('resources')->try();
            $environment = $context->get('environment')->try();

            global $argv;

            if ('' === $main) {
                return error('No main file specified. Use `--main=src/main.php` to specify a main file.');
            }

            if ($watch) {
                $arguments   = array_filter(array_slice($argv, 1), fn ($option) => trim($option) !== '--watch');
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
        });
    }
}