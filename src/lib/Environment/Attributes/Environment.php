<?php
namespace CatPaw\Environment\Attributes;

use Attribute;
use CatPaw\Attributes\Interfaces\AttributeInterface;
use CatPaw\Attributes\Traits\CoreAttributeDefinition;
use CatPaw\Attributes\{Entry, File};
use CatPaw\Environment\Services\EnvironmentService;

#[Attribute]
class Environment implements AttributeInterface {
    use CoreAttributeDefinition;
    
    /** @var array<string|File> */
    private array $files;

    /**
     * Set a list of environment file candidates.
     * 
     * The first valid file will be used as the application environment file.
     * 
     * @param array<string|File> $files files to try load
     * 
     * Some examples:
     * 
     * - `#[Environment( new File('./env.yml') )]`
     * - `#[Environment( new File('./.env') )]`
     * - `#[Environment( new File('./env.yml', true) )]`
     * - `#[Environment( new File('./env.yml'), new File('./resource/env.yml') )]` this falls back to `./resources/env.yml`
     * - `#[Environment( './env.yml' )]`
     * 
     * ### Note
     * 
     * The second `#[Environment( new File('./env.yml', true) )]` 
     * example will first lookup `./env.yml` outside 
     * the phar archive and then, if the file doesn't exist, will 
     * fallback to the phar archive `./env.yml` file.
     * 
     * ### Note 2
     * 
     * The last example `#[Environment( './env.yml' )]` is equivalent 
     * to  the first example `#[Environment( new File( './env.yml' ) )]`.
     * 
     * It's just a shorthand and there's no way to specify the 
     * second `$external` parameter in that case.
     */
    public function __construct(
        string|File ...$files,
    ) {
        if (count($files) === 0) {
            $files = [ new File("./resources/.env"), new File("./resources/env.yml") ];
        }

        $this->files = [];

        foreach ($files as $file) {
            if (is_string($file)) {
                $file = new File($file);
            }
            $this->files[] = $file;
        }
    }

    /**
     * This will set the given file names to the EnvironmentConfigurationService.
     */
    #[Entry] public function main(EnvironmentService $environmentService) {
        $environmentService->setFiles(...$this->files);
        $environmentService->load();
    }
}