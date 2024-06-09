<?php
namespace CatPaw\Web\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

interface ViewEngineInterface {
    /**
     * Configure the temporary directory where components will be cached.
     * @param  string              $temporaryDirectory
     * @return ViewEngineInterface
     */
    public function withTemporaryDirectory(string $temporaryDirectory):self;

    /**
     * Get the location of the temporary directory.
     * @return string
     */
    public function getTemporaryDirectoryLocation():string;

    /**
     * Load components from a directory recursively.
     * 
     * Each component name is resolved based on its path name relative to the given `$directoryName` to load.\
     * For example, if the `$directoryName` to load is named `/home/user/project/components`, then a file named `/home/user/project/components/buttons/red-button.xyz` will create a component called `buttons/red-button`, which you can import in your templates, extend or use any other way you would normally use any template.
     * @param  string       $directoryName
     * @return Unsafe<None>
     */
    public function loadComponentsFromDirectory(string $directoryName):Unsafe;

    /**
     * @param  string       $componentName The name of the component.
     * @param  string       $short         A short name for the component.
     * @param  string       $fileName      The name of the file containing the source code of the component.
     * @return Unsafe<None>
     */
    public function loadComponentFromFile(string $componentName, string $short, string $fileName):Unsafe;



    /**
     * @param  string       $componentName The name of the component.
     * @param  string       $short         A short name for the component.
     * @param  string       $source        The source code of the component.
     * @return Unsafe<None>
     */
    public function loadComponentFromSource(string $componentName, string $short, string $source):Unsafe;

    /**
     * Render a component.
     * @param  string              $componentName
     * @param  array<string,mixed> $params
     * @return Unsafe<string>
     */
    public function render(string $componentName, array $params):Unsafe ;
}