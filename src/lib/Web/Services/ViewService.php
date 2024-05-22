<?php
namespace CatPaw\Web\Services;

use CatPaw\Core\Attributes\Entry;
use CatPaw\Core\Attributes\Service;
use CatPaw\Core\Directory;

use function CatPaw\Core\error;
use CatPaw\Core\File;

use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Twig\TwigAsyncFilesystemLoader;
use Psr\Log\LoggerInterface;
use Throwable;
use Twig\Environment;

#[Service]
class ViewService {
    private LoggerInterface $logger;
    private Environment $environment;
    private TwigAsyncFilesystemLoader $loader;
    /** @var array<string,string> */
    private array $realFileNames = [];

    #[Entry] function start(LoggerInterface $logger):void {
        $this->logger = $logger;
    }

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
     * Load view components from a directory recursively.
     * 
     * 
     * Each component name is resolved based on its path name relative to the given `$directoryName` to load.\
     * For example, if the `$directoryName` to load is named `/home/user/project/components`, then a file named `/home/user/project/components/buttons/red-button.twig` will create a component called `buttons/red-button`, which you can import in your twig templates, extend or use any other way you would normally use any twig template.
     * @param  string       $directoryName
     * @return Unsafe<None>
     */
    public function loadComponentsFromDirectory(string $directoryName):Unsafe {
        // Find directory.
        if (!File::exists($directoryName)) {
            return error("Directory `$directoryName` not found.");
        }

        $fileNames = Directory::list($directoryName)->unwrap($error);
        if ($error) {
            return error($error);
        }
            
        // Load components.
        foreach ($fileNames as $fileName) {
            $componentName = str_replace("$directoryName/", '', $fileName);
            $this->loadComponent($fileName, $componentName)->unwrap($error);
            if ($error) {
                $this->logger->error("Error while trying to load component `$componentName`.\n");
                return error($error);
            }
            $this->logger->info("Component `$componentName` loaded.\n");
        }
        return ok();
    }

    /**
     * Load a view component.
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
     * Load view source code as a component.
     * @param  string       $source
     * @param  string       $componentName
     * @return Unsafe<None>
     */
    public function loadSourceAsComponent(string $source, string $componentName):Unsafe {
        if (!isset($this->environment)) {
            $this->loader      = TwigAsyncFilesystemLoader::create();
            $this->environment = new Environment($this->loader);
        }

        $this->loader->loadSourceAsFile($source, $componentName)->unwrap($error);
        if ($error) {
            return error($error);
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
