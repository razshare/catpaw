<?php
namespace CatPaw\Web\Services;

use CatPaw\Core\Attributes\Service;
use function CatPaw\Core\error;

use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Web\TwigAsyncFilesystemLoader;
use Throwable;
use Twig\Environment;

#[Service]
class TwigService {
    private Environment $environment;
    private TwigAsyncFilesystemLoader $loader;
    /** @var array<string,string> */
    private array $realFileNames = [];


    private function realFileName(string $fileName):string {
        if (isset($this->realFileNames[$fileName])) {
            $realFileName = $this->realFileNames[$fileName];
        } else {
            $realFileName                   = realpath($fileName);
            $this->realFileNames[$fileName] = $realFileName;
        }

        return $realFileName;
    }

    /**
     * 
     * @param  string       $fileName
     * @param  string       $componentName
     * @return Unsafe<None>
     */
    public function loadComponent(string $fileName, string $componentName):Unsafe {
        if (!isset($this->environment)) {
            $this->loader      = TwigAsyncFilesystemLoader::create();
            $this->environment = new Environment($this->loader);
        }
        $fileName = $this->realFileName($fileName);

        if ('' === $fileName) {
            return error("Twig file `$fileName` not found.");
        }

        if (!$this->loader->exists($fileName)) {
            $this->loader->loadFromFile($fileName)->unwrap($error);
            if ($error) {
                return error($error);
            }
        }

        if ('' !== $componentName && $componentName !== $fileName) {
            $this->loader->setAlias($componentName, $fileName);
        }

        return ok();
    }

    /**
     * Render a twig file.
     * @param  string              $fileName
     * @param  array<string,mixed> $context
     * @return Unsafe<string>
     */
    public function file(string $fileName, array $context = []): Unsafe {
        if (!isset($this->environment)) {
            $this->loader      = TwigAsyncFilesystemLoader::create();
            $this->environment = new Environment($this->loader);
        }
        try {
            $realFileName = $this->realFileName($fileName);
            if ('' === $realFileName) {
                return error("Twig file `$fileName` not found.");
            }

            $fileName = $realFileName;

            $this->loadComponent($fileName, $fileName)->unwrap($error);
            if ($error) {
                return error($error);
            }
            $template = $this->environment->load($fileName);
            return ok($template->render($context));
        } catch(Throwable $error) {
            return error($error);
        }
    }
}
