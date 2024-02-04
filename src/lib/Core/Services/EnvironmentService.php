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
     * Merges `$_ENV` into this service's internal map of variables.\
     * \
     * This could potentially overwrite some user defined variables.\
     * You can recover these _lost_ variables by invoking `load()` again.
     * @return void
     */
    public function includeSystemEnvironment() {
        $this->variables = [
            ...$this->variables,
            ...$_ENV,
        ];
    }

    /**
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.\
     * This function is invoked automatically when the application starts.
     * @param  bool         $info if true, feedback messages will be logged, otherwise the loading process will be silent.
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

        $file = File::open($fileName)->try($error);
        if ($error) {
            return error($error);
        }

        $content = $file->readAll()->await()->try($error);
        if ($error) {
            return error($error);
        }


        if (trim($content) !== '') {
            if (str_ends_with($fileName, '.yaml') || str_ends_with($fileName, '.yml')) {
                if (function_exists('yaml_parse')) {
                    $vars = yaml_parse($content);
                    if (false === $vars) {
                        return error("Error while parsing environment yaml file.");
                    }
                    $this->variables = $vars?:[];
                } else {
                    return error("Could not parse environment file, the yaml extension is needed in order to parse yaml environment files.");
                }
            } else {
                $this->variables = Dotenv::parse($content);
            }
        }

        return ok();
    }

    /**
     * Find an environment variable by name.
     *
     * ## Example
     * ```php
     * $service->findByName("server")['www'];
     * // or better even
     * $service->$findByName("server.www");
     * ```
     * @template T
     * @param  string $query name of the variable or a query in the form of `"key.subkey"`.
     * @return T      value of the variable.
     */
    public function findByName(string $query):mixed {
        if (isset($this->variables[$query])) {
            return $this->variables[$query];
        }
        $reference = &$this->variables;
        foreach (explode('.', $query) as $key) {
            if (!isset($reference[$key])) {
                return null;
            }
            $reference = &$reference[$key];
        }

        if ($reference === $this->variables) {
            // When reference has not changed it
            // means `$name` is invalid or was not found.
            return null;
        }

        return $reference;
    }
}
