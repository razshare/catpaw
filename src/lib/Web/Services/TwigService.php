<?php
namespace CatPaw\Web\Services;

use function CatPaw\Core\anyError;
use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use Catpaw\Web\TwigAsyncFilesystemLoader;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[Service]
class TwigService {
    private Environment $environment;

    /**
     * @param  string       $directoryName
     * @return Unsafe<void>
     */
    public function load(string $directoryName): Unsafe {
        return anyError(function() use ($directoryName) {
            $loader = TwigAsyncFilesystemLoader::create($directoryName, static function(string $fileName) use ($directoryName) {
                $fileNameWithoutExtension = preg_replace('/\.twig$/', '', $fileName);
                $quotedDirectoryName      = preg_quote("$directoryName/", '/');
                return preg_replace("/^$quotedDirectoryName/", '', $fileNameWithoutExtension);
            })
                ->try($error)
            or yield $error;

            $this->environment = new Environment($loader);
        });
    }

    /**
     * @param  string         $name
     * @return Unsafe<string>
     */
    public function render(string $name): Unsafe {
        try {
            $template = $this->environment->load($name);
            return ok($template->render());
        } catch(LoaderError|RuntimeError|SyntaxError $error) {
            return error($error);
        }
    }
}
