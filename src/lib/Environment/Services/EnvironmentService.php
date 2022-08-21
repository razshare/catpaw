<?php
namespace CatPaw\Environment\Services;

use function Amp\call;
use function Amp\File\exists;

use Amp\File\File;
use Amp\File\Filesystem;
use function Amp\File\openFile;

use Amp\Loop;
use Amp\Promise;
use CatPaw\Amp\File\CatPawDriver;
use CatPaw\Attributes\Entry;

use CatPaw\Attributes\Service;
use CatPaw\Environment\Exceptions\EnvironmentNotFoundException;
use function CatPaw\isPhar;
use Error;
use Phar;
use Psr\Log\LoggerInterface;

#[Service]
class EnvironmentService {
    /** @var array<string,string|null> */
    private array $variables = [];

    /**
     * Find the first available environment file name.
     * @throws Error
     * @return Promise<string>
     */
    private function findFileName(EnvironmentConfigurationService $environmentConfigurationService):Promise {
        return call(function() use ($environmentConfigurationService) {
            $isPhar    = isPhar();
            $phar      = Phar::running();
            $fileNames = $environmentConfigurationService->getFileNames();
            foreach ($fileNames as $i => $currentFileName) {
                if (yield exists($currentFileName)) {
                    return $currentFileName;
                }
                if ($isPhar) {
                    $currentFileName = "$phar/$currentFileName";
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
    #[Entry]
    public function load(
        LoggerInterface $logger,
        EnvironmentConfigurationService $environmentConfigurationService
    ):Promise {
        return call(function() use ($logger, $environmentConfigurationService) {
            $stringifiedFileNames = join(',', $environmentConfigurationService->getFileNames());
            
            if (!$fileName = yield $this->findFileName($environmentConfigurationService)) {
                throw new EnvironmentNotFoundException("Environment files [$stringifiedFileNames] not found.");
            }
            $logger->info("Environment file is $fileName");

            /** @var File $file */
            $file = yield openFile($fileName, 'r');

            $contents = '';

            while ($chunk = yield $file->read(65536)) {
                $contents .= $chunk;
            }
            
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