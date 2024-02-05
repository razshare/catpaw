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
    private array $variables = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @var string */
    private string $fileName = './env.yaml';


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
     * Parse the first valid environment file and update all variables in memory.
     * Multiple calls are allowed.\
     * This function is invoked automatically when the application starts.
     * @param  bool         $skipErrors When set to `true`, the function will never return any errors.
     * @return Unsafe<void>
     */
    public function load(bool $skipErrors = false):Unsafe {
        $this->variables = [];
        $fileName        = $this->fileName;

        if (!File::exists($fileName)) {
            $variants = [];

            if (str_ends_with($fileName, '.yml')) {
                $variants[] = substr($fileName, -3).'.yaml';
            } else if (str_ends_with($fileName, '.yaml')) {
                $variants[] = substr($fileName, -5).'.yml';
            } else {
                $variants[] = "$fileName.yaml";
                $variants[] = "$fileName.yml";
                $variants[] = ".$fileName";
            }

            foreach ($variants as $variant) {
                if (!str_starts_with($variant, '/') && !str_starts_with($variant, '../') && !str_starts_with($variant, './')) {
                    $variant = "./$variant";
                }

                if (File::exists($variant)) {
                    $fileName = $variant;
                    break;
                }
            }
        } else {
            if (!str_starts_with($fileName, '/') && !str_starts_with($fileName, '../') && !str_starts_with($fileName, './')) {
                $fileName = "./$fileName";
            }
        }


        $file = File::open($fileName)->try($error);
        if ($error) {
            if ($skipErrors) {
                return ok();
            }
            return error($error);
        }

        $content = $file->readAll()->await()->try($error);
        if ($error) {
            if ($skipErrors) {
                return ok();
            }
            return error($error);
        }


        if (trim($content) !== '') {
            if (str_ends_with($fileName, '.yaml') || str_ends_with($fileName, '.yml')) {
                if (function_exists('yaml_parse')) {
                    $vars = yaml_parse($content);
                    if (false === $vars) {
                        if ($skipErrors) {
                            return ok();
                        }
                        return error("Error while parsing environment yaml file.");
                    }
                    $this->variables = $vars?:[];
                } else {
                    if ($skipErrors) {
                        return ok();
                    }
                    return error("Could not parse environment file, the yaml extension is needed in order to parse yaml environment files.");
                }
            } else {
                $this->variables = Dotenv::parse($content);
            }
        }

        return ok();
    }

    /**
     *
     * @param  mixed  $value
     * @param  string $key
     * @return void
     */
    public function set(string $query, $value) {
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
     * @template T
     * @param  string $query name of the variable or a query in the form of `"key.subkey"`.
     * @return T      value of the variable.
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
