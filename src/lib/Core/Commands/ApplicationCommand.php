<?php
namespace CatPaw\Core\Commands;

use CatPaw\Core\Bootstrap;
use CatPaw\Core\CommandBuilder;
use CatPaw\Core\CommandContext;
use function CatPaw\Core\error;
use CatPaw\Core\FileName;
use CatPaw\Core\Interfaces\CommandInterface;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;

readonly class ApplicationCommand implements CommandInterface {
    public function build(CommandBuilder $builder):void {
        $builder->optional('m', 'main');
        $builder->optional('p', 'php');
        $builder->optional('i', 'initializer');
        $builder->optional('s', 'spawner');
        $builder->optional('e', 'environment');
        $builder->optional('n', 'name');
        $builder->optional('d', "die-on-stdin");
        $builder->optional('w', "watch");
        $builder->optional('l', 'libraries');
        $builder->optional('r', 'resources');
    }

    public function run(CommandContext $context):Result {
        $dieOnStdin  = (bool)$context->get('die-on-stdin');
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
        
        if ($environment) {
            $environment = realpath($environment);
        }

        Bootstrap::start(
            main: $main,
            name: $name,
            libraries: $libraries,
            resources: $resources,
            environment: $environment,
            dieOnStdin: $dieOnStdin,
        );
        
        return ok();
    }
}