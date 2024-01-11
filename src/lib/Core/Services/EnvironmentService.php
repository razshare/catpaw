<?php
namespace CatPaw\Core\Services;

use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;

use Dotenv\Dotenv;
use function function_exists;
use Psr\Log\LoggerInterface;
use function str_ends_with;
use function yaml_parse;

#[Service]
class EnvironmentService {
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @var string */
    private string $fileName = '';

    /**
     * Set the environment file name.
     * @param  string $fileName
     * @return void
     */
    public function setFileName(string $fileName):void {
        $this->fileName = $fileName;
    }


    /** @var array<string,string|null> */
    private array $variables = [];

    /**
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.
     * @param  bool         $info if true, feedback messages will be written to stdout, otherwise the loading process will be silent.
     * @return Unsafe<void>
     */
    public function load(bool $info = false):Unsafe {
        $this->variables = [];
            
        if (!$fileName = $this->fileName) {
            if ($info) {
                $this->logger->info("Environment file $this->fileName not found.");
            }
            return ok();
        }

        if ($info) {
            $this->logger->info("Environment file is $fileName");
        }

        $file = File::open($fileName);
        if ($file->error) {
            return error($file->error);
        }

        $read = $file->value->readAll()->await();
        if ($read->error) {
            return error($read->error);
        }

        $contents = $read->value;

            
        if (trim($contents) !== '') {
            if (str_ends_with($fileName, '.yaml') || str_ends_with($fileName, '.yml')) {
                if (function_exists('yaml_parse')) {
                    $vars = yaml_parse($contents);
                    if (false === $vars) {
                        return error("Error while parsing environment yaml file.");
                    }
                    $this->variables = $vars?:[];
                } else {
                    return error("Could not parse environment file, the yaml extension is needed in order to parse yaml environment files.");
                }
            } else {
                $this->variables = Dotenv::parse($contents);
            }
        }

        $_ENV = [
            ...$_ENV,
            ... $this->variables,
        ];

        return ok();
    }

    /**
     * Get all the environment variables
     * @return array<string,string|null>
     */
    public function getVariables():array {
        return $this->variables;
    }
}