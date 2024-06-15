<?php
namespace CatPaw\Web\Implementations\ViewEngine;

use CatPaw\Core\Attributes\Provider;
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

#[Provider]
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

        $fileNames = Directory::flat($directoryName)->unwrap($error);
        if ($error) {
            return error($error);
        }

        foreach ($fileNames as $fileName) {
            if (!str_ends_with($fileName, '.latte')) {
                continue;
            }
            $componentFullName = str_replace("$directoryName/", '', $fileName);
            $shortAlias        = preg_replace('/.\w+$/', '', $componentFullName);
            $this->loadComponentFromFile($componentFullName, [$shortAlias, $fileName], $fileName)->unwrap($error);
            if ($error) {
                $this->logger->error("Error while trying to load component `$componentFullName`.");
                return error($error);
            }
            $this->logger->info("Component `$componentFullName` loaded.");
        }
        return ok();
    }
    
    /**
     * @param  string        $componentFullName
     * @param  array<string> $componentAliases
     * @param  string        $fileName
     * @return Unsafe<None>
     */
    public function loadComponentFromFile(string $componentFullName, array $componentAliases, string $fileName):Unsafe {
        $file = File::open($fileName)->unwrap($error);
        if ($error) {
            return error($error);
        }

        $source = $file->readAll()->unwrap($error);
        if ($error) {
            return error($error);
        }
        $file->close();

        return $this->loadComponentFromSource($componentFullName, $componentAliases, $source);
    }


    /**
     * @param  string        $componentFullName
     * @param  array<string> $componentAliases
     * @param  string        $source
     * @return Unsafe<None>
     */
    public function loadComponentFromSource(string $componentFullName, array $componentAliases, string $source):Unsafe {
        $this->components[$componentFullName] = &$source;
        foreach ($componentAliases as $componentAlias) {
            $this->components[$componentAlias] = &$source;
        }
        $this->numberOfComponents = count($this->components);
        return ok();
    }

    private function update():void {
        if ($this->numberOfComponents !== $this->numberOfComponentsOnLastRender) {
            $this->numberOfComponentsOnLastRender = $this->numberOfComponents;
            $this->latte->setLoader(new StringLoader($this->components));
        }
    }

    /**
     * 
     * @param  string $name
     * @return bool
     */
    public function hasComponent(string $name): bool {
        return isset($this->components[$name]);
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
