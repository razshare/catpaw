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
use Twig\Source;
use Twig\TemplateWrapper;

#[Service]
class TwigService {
    private Environment $environment;
    private TwigAsyncFilesystemLoader $loader;
    /** @var array<string> */
    private array $realFileNamesMap = [];
    /** @var array<string,TemplateWrapper> */
    private array $templates = [];

    /**
     * Render twig source code.
     * @param  string              $source
     * @param  array<string,mixed> $context
     * @return Unsafe<string>
     */
    public function source(string $source, array $context = []):Unsafe {
        if (!isset($this->environment)) {
            $this->loader      = TwigAsyncFilesystemLoader::create();
            $this->environment = new Environment($this->loader);
        }

        try {
            $templateName = md5($source);

            if (!isset($this->templates[$templateName])) {
                $template = $this->environment->createTemplate($source, $templateName);
            } else {
                $template = $this->templates[$templateName];
            }

            return ok($template->render($context));
        } catch(LoaderError|RuntimeError|SyntaxError $error) {
            return error($error);
        }
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
                $this->loader->loadFromFile($realFileName)->unwrap($error);
                if ($error) {
                    return error($error);
                }
            }
            $template = $this->environment->load($fileName);
            return ok($template->render($context));
        } catch(LoaderError|RuntimeError|SyntaxError $error) {
            return error($error);
        }
    }
}
