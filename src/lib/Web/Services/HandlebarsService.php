<?php
namespace CatPaw\Web\Services;

use function CatPaw\Core\anyError;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\Directory;
use function CatPaw\Core\error;
use CatPaw\Core\File;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use LightnCandy\LightnCandy;

#[Service]
class HandlebarsService {
    /** @var array<string,callable(array<string,mixed>):string> */
    private array $cache               = [];
    private string $temporaryDirectory = '.tmp/handlebars';

    /**
     * Where to save the compiled templates.
     * @param  string            $temporaryDirectory
     * @return HandlebarsService
     */
    public function withTemporaryDirectory(string $temporaryDirectory):self {
        $this->temporaryDirectory = $temporaryDirectory;
        return $this;
    }

    /**
     *
     * @param  string              $source
     * @param  array<string,mixed> $context
     * @param  string              $id
     * @return Unsafe<string>
     */
    public function source(string $source, array $context, string $id = ''):Unsafe {
        $temporaryDirectory = $this->temporaryDirectory;
        return anyError(function() use ($source, $id, $context, $temporaryDirectory) {
            if ('' === $id) {
                $id = hash('xxh3', $source).'.php';
            }
            
            $id = hash('xxh3', $id).'.php';

            if (isset($this->cache[$id])) {
                $function = $this->cache[$id];
                return ok($function($context));
            }

            Directory::create($temporaryDirectory)->try();


            $fileName = "{$temporaryDirectory}/$id";

            $file = File::open($fileName, 'w+')->try();

            $compiledPhpFunction = LightnCandy::compile($source, [
                'flags' => LightnCandy::FLAG_ERROR_LOG | LightnCandy::FLAG_STANDALONEPHP,
            ]);

            if (!$compiledPhpFunction) {
                echo PHP_EOL.PHP_EOL.PHP_EOL.$source.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL;
                return error("Could not compile handlebars source.");
            }

            $file->write(
                <<<PHP
                    <?php
                    $compiledPhpFunction
                    PHP
            )->try();
            $file->close();

            $function         = require_once $fileName;
            $this->cache[$id] = $function;
            return ok($function($context));
        });
    }

    /**
     *
     * @param  string              $fileName
     * @param  array<string,mixed> $context
     * @return Unsafe<string>
     */
    public function file(string $fileName, array $context): Unsafe {
        $temporaryDirectory = $this->temporaryDirectory;

        $file = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $source = $file->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }

        // This is necessary because the code above is async, 
        // and we want to maintain the fluent api in case userland wants
        // to change the temporary directory specifically just for the current execution.
        $this->temporaryDirectory = $temporaryDirectory;
        return $this->source($source, $context, $fileName);
    }
}
