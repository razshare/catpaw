<?php
namespace CatPaw\Core\Services;

use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\None;

use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;

use Dotenv\Dotenv;
use function function_exists;
use Psr\Log\LoggerInterface;
use function str_ends_with;
use function yaml_parse;

#[Service]
class EnvironmentService {
    /** @var array<mixed> */
    private array $variables = [];

    public function __construct(
        // @phpstan-ignore-next-line
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @var string */
    private string $fileName = './env.yaml';


    /**
     * Merge `$_ENV` and `getenv()` with this service's internal variables map.\
     * \
     * This may overwrite keys defined in your environment file.\
     * Call `load()` again to recover the lost keys.
     * @return void
     */
    public function includeSystemEnvironment() {
        $this->variables = [
            ...$this->variables,
            ...$_ENV,
            ...getenv(),
        ];
    }


    /**
     * Set the environment file name.
     * @param  string $fileName
     * @return void
     */
    public function setFileName(string $fileName):void {
        $this->fileName = $fileName;
    }

    /**
     * Clear all environment variables.
     * @return void
     */
    public function clear() {
        $this->variables = [];
    }

    /**
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.\
     * This function is invoked automatically when the application starts.
     * @return Unsafe<None>
     */
    public function load():Unsafe {
        $fileName = $this->fileName;

        $file = File::open($fileName)->try($error);
        if ($error) {
            return error($error);
        }

        $content = $file->readAll()->try($error);
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
                    $this->variables = [
                        ...$this->variables,
                        ...($vars?:[]),
                    ];
                } else {
                    return error("Could not parse environment file, the yaml extension is needed in order to parse yaml environment files.");
                }
            } else {
                $this->variables = [
                    ...$this->variables,
                    ...Dotenv::parse($content),
                ];
            }
        }

        return ok();
    }

    /**
     *
     * @param  string $query
     * @param  mixed  $value
     * @return void
     */
    public function set(string $query, mixed $value) {
        $reference = &$this->variables;
        foreach (explode('.', $query) as $key) {
            if (!isset($reference[$key])) {
                $reference[$key] = [];
            }
            $reference = &$reference[$key];
        }

        if ($reference === $this->variables) {
            // When reference has not changed it
            // means `$name` is invalid or was not found.
            return;
        }

        $reference = $value;
    }

    /**
     * Find an environment variable by name.
     *
     * ## Example
     * ```php
     * $service->get("server")['www'];
     * // or better even
     * $service->$get("server.www");
     * ```
     * @param  string $query name of the variable or a query in the form of `"key.subkey"`.
     * @return mixed  value of the variable.
     */
    public function get(string $query):mixed {
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
