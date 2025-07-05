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
    public function __construct(private string $startFileName) {
    }

    public function build(CommandBuilder $builder):void {
        $builder->optional('m', 'main');
        $builder->optional('p', 'php');
        $builder->optional('s', 'spawner');
        $builder->optional('e', 'environment');
        $builder->optional('n', 'name');
        $builder->optional('l', 'libraries');
        $builder->optional('r', 'resources');
    }

    public function run(CommandContext $context):Result {
        global $argv;
        
        $spawner     = $context->get('spawner')?:$context->get('php')?:'';
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

        if ($spawner) {
            $arguments = array_filter(array_slice($argv, 1), function($option) {
                if (str_starts_with(trim($option), '--spawner')) {
                    return false;
                }

                if (str_starts_with(trim($option), '-s')) {
                    return false;
                }

                return true;
            });
            Bootstrap::spawn(
                spawner: $spawner?:'/usr/bin/php',
                fileName: $this->startFileName,
                arguments: $arguments,
            );
        } else {
            Bootstrap::start(
                main: $main,
                name: $name,
                libraries: $libraries,
                resources: $resources,
                environment: $environment,
            );
        }
        return ok();
    }
}