<?php
namespace CatPaw\Core\Implementations\Environment;

use CatPaw\Core\Attributes\Provider;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use CatPaw\Core\Interfaces\EnvironmentInterface;
use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Result;
use Dotenv\Dotenv;
use function function_exists;
use Psr\Log\LoggerInterface;
use function str_ends_with;
use function yaml_parse;

#[Provider]
class SimpleEnvironment implements EnvironmentInterface {
    /** @var array<mixed> */
    private array $variables = [];

    public function __construct(
        // @phpstan-ignore-next-line
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @var string */
    private string $fileName = 'env.ini';


    /**
     * Merge `$_ENV` and `getenv()` with this service's internal variables map.\
     * \
     * This may overwrite keys defined in your environment file.\
     * Call `load()` again to recover the lost keys.
     * @return self
     */
    public function includeSystemEnvironment(): self {
        $this->variables = [
            ...$this->variables,
            ...$_ENV,
            ...getenv(),
        ];
        return $this;
    }


    /**
     * Set the environment file name.
     * @param  string $fileName
     * @return self
     */
    public function withFileName(string $fileName): self {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Clear all environment variables.
     * @return void
     */
    public function clear(): void {
        $this->variables = [];
    }

    /**
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.\
     * This function is invoked automatically when the application starts.
     * @return Result<None>
     */
    public function load(): Result {
        $fileName = $this->fileName;

        $file = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $contents = $file->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }


        if (trim($contents) !== '') {
            if (str_ends_with($fileName, '.yaml') || str_ends_with($fileName, '.yml')) {
                if (function_exists('yaml_parse')) {
                    $vars = yaml_parse($contents);
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
            } else if (str_ends_with($fileName, '.ini')) {
                $this->variables = [
                    ...$this->variables,
                    ...parse_ini_string(ini_string: $contents, process_sections: true),
                ];
            } else {
                $this->variables = [
                    ...$this->variables,
                    ...Dotenv::parse($contents),
                ];
            }
        }

        return ok();
    }

    /**
     *
     * @param  string $query
     * @param  mixed  $value
     * @return self
     */
    public function set(string $query, mixed $value): self {
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
            return $this;
        }

        $reference = $value;
        return $this;
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
    public function get(string $query): mixed {
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
