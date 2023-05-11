<?php
namespace CatPaw\Environment\Services;

use \CatPaw\Attributes\File as AttributeFile;
use function Amp\File\exists;
use Amp\File\File;

use Amp\File\Filesystem;
use function Amp\File\openFile;
use CatPaw\Amp\File\CatPawDriver;

use CatPaw\Attributes\Service;
use CatPaw\Environment\Exceptions\EnvironmentNotFoundException;
use function CatPaw\isPhar;
use Error;
use Phar;
use Psr\Log\LoggerInterface;
use Revolt\EventLoop;

#[Service]
class EnvironmentService {
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /** @var array<AttributeFile> */
    private array $files = [];

    /**
     * Set the allowed environment file names.
     * @param  array<string|AttributeFile> $files
     * @return void
     */
    public function setFiles(string|AttributeFile ...$files):void {
        $this->files = [];
        foreach ($files as $file) {
            if (is_string($file)) {
                $file = new AttributeFile($file);
            }
            $this->files[] = $file;
        }
    }


    /** @var array<string,string|null> */
    private array $variables = [];

    /**
     * Find the first available environment file name.
     * @throws Error
     * @return string
     */
    private function findFileName():string {
        $isPhar = isPhar();
        $phar   = Phar::running();
        foreach ($this->files as $_ => $currentFile) {
            $currentFileName = $currentFile->getFileName();
            if ($isPhar) {
                $allowsExternal = $currentFile->allowsExternal();
                if ($allowsExternal && exists($currentFileName)) {
                    return $currentFileName;
                }
                $currentPharFileName = "$phar/$currentFileName";
                if (exists($currentPharFileName)) {
                    return $currentPharFileName;
                }
            } else {
                if (exists($currentFileName)) {
                    return $currentFileName;
                }
            }
        }
        return '';
    }

    /**
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.
     * @return void
     */
    public function load():void {
        $this->variables = [];
            
        $fileNames = [];

        foreach ($this->files as $file) {
            $fileNames[] = $file->getFileName();
        }
            
        // $stringifiedFileNames = join(',', $fileNames);
            
        if (!$fileName = $this->findFileName()) {
            // throw new EnvironmentNotFoundException("Environment files [$stringifiedFileNames] not found.");
            // $this->logger->info("Environment files [$stringifiedFileNames] not found.");
            return;
        }
            
        if ($_ENV['SHOW_INFO'] ?? false) {
            $this->logger->info("Environment file is $fileName");
        }

        $file = openFile($fileName, 'r');

        $contents = '';

        while ($chunk = $file->read(null, 65536)) {
            $contents .= $chunk;
        }
            
        if (trim($contents) !== '') {
            if (\str_ends_with($fileName, '.yml') || \str_ends_with($fileName, '.yaml')) {
                if (\function_exists('yaml_parse')) {
                    $vars            = \yaml_parse($contents);
                    $this->variables = $vars?$vars:[];
                } else {
                    $this->logger->error("Could not parse environment file, the yaml extension is needed in order to parse yaml environment files.");
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

        /**
         * @psalm-suppress InvalidArrayOffset
         */
        if (isset($_ENV["CATPAW_FILE_DRIVER"]) && $_ENV["CATPAW_FILE_DRIVER"]) {
            /**
             * How would you set a new file system driver?
             * TODO: open a new issue about this in https://github.com/amphp/file/issues/new
             */
            // EventLoop::setState(\Amp\File\Driver::class, new Filesystem(new CatPawDriver));
        }
    }

    /**
     * Get all the environment variables
     * @return array<string,string|null>
     */
    public function getVariables():array {
        return $this->variables;
    }
}