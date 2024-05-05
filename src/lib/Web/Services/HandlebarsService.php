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
    private array $cache = [];

    /**
     *
     * @param  string              $source
     * @param  array<string,mixed> $context
     * @param  string              $id
     * @return Unsafe<string>
     */
    public function source(string $source, array $context, string $id = ''):Unsafe {
        return anyError(function() use ($source, $id, $context) {
            if ('' === $id) {
                $id = hash('xxh3', $source).'.php';
            }
            
            $id = hash('xxh3', $id).'.php';

            if (isset($this->cache[$id])) {
                $function = $this->cache[$id];
                return ok($function($context));
            }

            Directory::create('.tmp/handlebars')->try();


            $fileName = ".tmp/handlebars/$id";

            $file = File::open($fileName, 'w+')->try();

            $compiledPhpFunction = LightnCandy::compile($source, [
                'flags' => LightnCandy::FLAG_ERROR_LOG | LightnCandy::FLAG_STANDALONEPHP,
            ]);

            if (!$compiledPhpFunction) {
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
        $file = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }
        $source = $file->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }

        return $this->source($source, $context, $fileName);
    }
}
