<?php
namespace CatPaw\Web\Services;

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
    /** @var array<string> */
    private array $realFileNamesMap = [];

    /**
     * @param  string              $fileName
     * @param  array<string,mixed> $properties
     * @return Unsafe<string>
     */
    public function render(string $fileName, array $properties): Unsafe {
        if (!isset($this->environment)) {
            $this->loader      = TwigAsyncFilesystemLoader::create();
            $this->environment = new Environment($this->loader);
        }
        try {
            if (isset($this->realFileNamesMap[$fileName])) {
                $realFileName = $this->realFileNamesMap[$fileName];
            } else {
                $realFileName                      = realpath($fileName);
                $this->realFileNamesMap[$fileName] = $realFileName;
            }

            if (false === $realFileName) {
                return error("Received an invalid file name `$fileName`.");
            }

            if (!$this->loader->exists($realFileName)) {
                $this->loader->loadFromFile($realFileName)->try($error);
                if ($error) {
                    return error($error);
                }
            }
            $template = $this->environment->load($fileName);
            return ok($template->render($properties));
        } catch(LoaderError|RuntimeError|SyntaxError $error) {
            return error($error);
        }
    }
}
