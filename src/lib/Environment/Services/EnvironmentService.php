<?php
namespace CatPaw\Environment\Services;

use function Amp\call;
use function Amp\File\{exists, openFile};
use Amp\File\{File, Filesystem};
use Amp\{Loop, Promise};
use CatPaw\Amp\File\CatPawDriver;

use CatPaw\Attributes\Service;
use CatPaw\Environment\Exceptions\EnvironmentNotFoundException;
use function CatPaw\isPhar;
use Error;
use Phar;
use Psr\Log\LoggerInterface;

#[Service]
class EnvironmentService {
    public function __construct(private EnvironmentConfigurationService $environmentConfigurationService) {
    }

    /** @var array<string,string|null> */
    private array $variables = [];

    /**
     * Find the first available environment file name.
     * @throws Error
     * @return Promise<string>
     */
    private function findFileName():Promise {
        return call(function() {
            $isPhar    = isPhar();
            $phar      = Phar::running();
            $fileNames = $this->environmentConfigurationService->getFiles();
            foreach ($fileNames as $i => $currentFile) {
                $currentFileName = $currentFile->getFileName();
                if ($isPhar) {
                    $allowsExternal = $currentFile->allowsExternal();
                    if ($allowsExternal && yield exists($currentFileName)) {
                        return $currentFileName;
                    }
                    $currentPharFileName = "$phar/$currentFileName";
                    if (yield exists($currentPharFileName)) {
                        return $currentPharFileName;
                    }
                } else {
                    if (yield exists($currentFileName)) {
                        return $currentFileName;
                    }
                }
            }
            return '';
        });
    }

    /**
     * Parse the environment file  and update all variables in memory.
     * Multiple calls are allowed.
     * @return Promise<void>
     */
    public function load(
        LoggerInterface $logger,
        EnvironmentConfigurationService $environmentConfigurationService
    ):Promise {
        return call(function() use ($logger, $environmentConfigurationService) {
            $this->variables = [];

            $files     = $environmentConfigurationService->getFiles();
            $fileNames = [];

            foreach ($files as $file) {
                $fileNames[] = $file->getFileName();
            }
            
            $stringifiedFileNames = join(',', $fileNames);
            
            if (!$fileName = yield $this->findFileName($environmentConfigurationService)) {
                throw new EnvironmentNotFoundException("Environment files [$stringifiedFileNames] not found.");
            }
            
            if ($_ENV['SHOW_INFO']) {
                $logger->info("Environment file is $fileName");
            }

            /** @var File $file */
            $file = yield openFile($fileName, 'r');

            $contents = '';

            while ($chunk = yield $file->read(65536)) {
                $contents .= $chunk;
            }
            
            if (trim($contents) !== '') {
                if (\str_ends_with($fileName, '.yml') || \str_ends_with($fileName, '.yaml')) {
                    if (\function_exists('yaml_parse')) {
                        $this->variables = \yaml_parse($contents);
                    } else {
                        $logger->error("Could not parse environment file, the yaml extension is needed in order to parse yaml environment files.");
                        $this->variables = [];
                    }
                } else {
                    $this->variables = \Dotenv\Dotenv::parse($contents);
                }
            }

            $_ENV = [
                ...$_ENV,
                ... $this->variables,
            ];

            if (isset($_ENV["CATPAW_FILE_DRIVER"]) && $_ENV["CATPAW_FILE_DRIVER"]) {
                Loop::setState(\Amp\File\Driver::class, new Filesystem(new CatPawDriver));
            }
        });
    }

    /**
     * Get all the environment variables
     * @return array<string,string|null>
     */
    public function getVariables():array {
        return $this->variables;
    }
}