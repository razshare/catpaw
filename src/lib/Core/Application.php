<?php
namespace CatPaw\Core;

use CatPaw\Core\Interfaces\CommandRunnerInterface;

readonly class Application implements CommandRunnerInterface {
    public function build(CommandBuilder $builder): Unsafe {
        // Flags.
        $builder->withFlag('d', "die-on-change");
        $builder->withFlag('w', "watch");

        // Options.
        $builder->withOption('p', 'php', ok('php'));
        $builder->withOption('n', 'name', ok('App'));
        $builder->withOption('t', 'entry', error('Missing entry file.'));
        $builder->withOption('l', 'libraries');
        $builder->withOption('r', 'resources');
        $builder->withOption('e', 'environment');

        return ok();
    }

    public function run(CommandContext $context): Unsafe {
        return anyError(function() use ($context) {
            // Flags.
            $dieOnChange = $context->get('die-on-change')->try();
            $watch       = $context->get('watch')->try();
            
            // Options.
            $php         = $context->get('php')->try();
            $name        = $context->get('name')->try();
            $entry       = $context->get('entry')->try();
            $libraries   = $context->get('libraries')->try();
            $resources   = $context->get('resources')->try();
            $environment = $context->get('environment')->try();


            global $argv;

            if ('' === $entry) {
                return error('No entry point specified. Use `--entry=src/main.php` to specify an entry point.');
            }

            if ($watch) {
                $arguments   = array_filter(array_slice($argv, 1), fn ($option) => trim($option) !== '--watch');
                $arguments[] = '--die-on-change';
                Bootstrap::spawn(
                    binary: $php,
                    fileName: __FILE__,
                    arguments: $arguments,
                    entry: $entry,
                    libraries: $libraries,
                    resources: $resources,
                );
            } else {
                Bootstrap::start(
                    entry: $entry,
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