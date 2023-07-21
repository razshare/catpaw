<?php
namespace CatPaw\Services;

use function Amp\File\exists;
use function Amp\File\openFile;
use CatPaw\Attributes\Service;
use CatPaw\Bootstrap;

use Error;
use Psr\Log\LoggerInterface;

#[Service]
class EnvironmentService {
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /** @var array<string> */
    private array $files = [];

    /**
     * Set the allowed environment file names.
     * @param  array<string> $files
     * @return void
     */
    public function setFiles(array $files):void {
        $this->files = $files;
    }


    /** @var array<string,string|null> */
    private array $variables = [];

    /**
     * Find the first available environment file name.
     * @throws Error
     * @return string
     */
    private function findFileName():string {
        foreach ($this->files as $_ => $currentFileName) {
            if (exists($currentFileName)) {
                return $currentFileName;
            }
        }

        return '';
    }

    /**
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.
     * @param  bool $info if true, feedback messages will be written to stdout, otherwise the loading process will be silent.
     * @return void
     */
    public function load(bool $info = false):void {
        $this->variables = [];
        
        $stringifiedFileNames = join(',', $this->files);
            
        if (!$fileName = $this->findFileName()) {
            if ($info) {
                $this->logger->info("Environment files [$stringifiedFileNames] not found.");
            }
            return;
        }

        if ($info) {
            $this->logger->info("Environment file is $fileName");
        }

        $file = openFile($fileName, 'r');

        $contents = '';

        while ($chunk = $file->read()) {
            $contents .= $chunk;
        }
            
        if (trim($contents) !== '') {
            if (\str_ends_with($fileName, '.yml') || \str_ends_with($fileName, '.yaml')) {
                if (\function_exists('yaml_parse')) {
                    $vars            = \yaml_parse($contents);
                    $this->variables = $vars?$vars:[];
                } else {
                    Bootstrap::kill("Could not parse environment file, the yaml extension is needed in order to parse yaml environment files.");
                }
            } else {
                $this->variables = \Dotenv\Dotenv::parse($contents);
            }
        }

        $_ENV = [
            ...$_ENV,
            ... $this->variables,
        ];
    }

    /**
     * Get all the environment variables
     * @return array<string,string|null>
     */
    public function getVariables():array {
        return $this->variables;
    }
}