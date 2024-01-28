<?php
namespace CatPaw\Web\Services;

use function CatPaw\Core\anyError;
use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Web\TwigAsyncFilesystemLoader;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Service]
class TwigService {
    private Environment $environment;
    private TwigAsyncFilesystemLoader $loader;

    /**
     * @param  string       $directoryName
     * @return Unsafe<void>
     */
    public function load(string $directoryName): Unsafe {
        return anyError(function() use ($directoryName) {
            $this->loader = $loader = TwigAsyncFilesystemLoader::create($directoryName, static function(string $fileName) use ($directoryName) {
                $quotedDirectoryName = preg_quote("$directoryName/", '/');
                return preg_replace("/^$quotedDirectoryName/", '', $fileName);
            })
                ->try($error)
            or yield $error;

            $this->environment = new Environment($loader);
        });
    }

    /**
     * @param  string         $name
     * @param  array          $properties
     * @return Unsafe<string>
     */
    public function render(string $name, array $properties): Unsafe {
        try {
            if (!$this->loader->exists($name)) {
                $this->loader->loadFromFile($name)->try($error);
                if ($error) {
                    return error($error);
                }
            }
            $template = $this->environment->load($name);
            return ok($template->render($properties));
        } catch(LoaderError|RuntimeError|SyntaxError $error) {
            return error($error);
        }
    }
}
