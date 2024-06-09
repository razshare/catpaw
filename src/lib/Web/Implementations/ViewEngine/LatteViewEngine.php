<?php
namespace CatPaw\Web\Implementations\ViewEngine;

use CatPaw\Core\Directory;

use function CatPaw\Core\error;
use CatPaw\Core\File;

use CatPaw\Core\None;
use function CatPaw\Core\ok;
use CatPaw\Core\Unsafe;
use CatPaw\Web\Interfaces\ViewEngineInterface;
use Latte\Engine;
use Latte\Loaders\StringLoader;
use Psr\Log\LoggerInterface;
use Throwable;

class LatteViewEngine implements ViewEngineInterface {
    private Engine $latte;
    private string $temporaryDirectory = '.tmp';
    /** @var array<string,string> */
    private array $components                   = [];
    private int $numberOfComponents             = 0;
    private int $numberOfComponentsOnLastRender = 0;

    public function __construct(public readonly LoggerInterface $logger) {
        $this->latte = new Engine;
    }

    public function withTemporaryDirectory(string $temporaryDirectory):self {
        $this->temporaryDirectory = $temporaryDirectory;
        return $this;
    }

    public function getTemporaryDirectoryLocation():string {
        return $this->temporaryDirectory;
    }

    /**
     * @param  string       $directoryName
     * @return Unsafe<None>
     */
    public function loadComponentsFromDirectory(string $directoryName):Unsafe {
        if (!File::exists($directoryName)) {
            return error("Directory `$directoryName` not found.");
        }

        $fileNames = Directory::list($directoryName)->unwrap($error);
        if ($error) {
            return error($error);
        }

        foreach ($fileNames as $fileName) {
            $componentName = str_replace("$directoryName/", '', $fileName);
            $this->loadComponentFromFile($fileName, $fileName, $componentName)->unwrap($error);
            if ($error) {
                $this->logger->error("Error while trying to load component `$componentName`.\n");
                return error($error);
            }
            $this->logger->info("Component `$componentName` loaded.\n");
        }
        return ok();
    }
    
    /**
     * @param  string       $componentName
     * @param  string       $short
     * @param  string       $fileName
     * @return Unsafe<None>
     */
    public function loadComponentFromFile(string $componentName, string $short, string $fileName):Unsafe {
        $file = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $source = $file->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }

        return $this->loadComponentFromSource($source, $componentName, $short);
    }


    /**
     * @param  string       $componentName
     * @param  string       $short
     * @param  string       $source
     * @return Unsafe<None>
     */
    public function loadComponentFromSource(string $componentName, string $short, string $source):Unsafe {
        $this->components[$componentName] = &$source;
        $this->components[$short]         = &$source;
        $this->numberOfComponents         = count($this->components);
        return ok();
    }

    private function update():void {
        if ($this->numberOfComponents !== $this->numberOfComponentsOnLastRender) {
            $this->numberOfComponentsOnLastRender = $this->numberOfComponents;
            $this->latte->setLoader(new StringLoader($this->components));
        }
    }

    /**
     * @param  string              $filename
     * @param  array<string,mixed> $params
     * @return Unsafe<string>
     */
    public function render(string $filename, array $params):Unsafe {
        $this->update();
        try {
            return ok($this->latte->renderToString($filename, $params));
        } catch(Throwable $error) {
            return error($error);
        }
    }
}
