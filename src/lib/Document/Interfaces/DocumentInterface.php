<?php
namespace CatPaw\Document\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;
use CatPaw\Document\MountContext;
use CatPaw\Document\Render;
use CatPaw\Web\Body;
use CatPaw\Web\Interfaces\ResponseModifier;
use CatPaw\Web\Query;

interface DocumentInterface {
    /**
     * Mount a document by file name.
     * @param  string                                    $fileName
     * @param  false|callable(MountContext):Result<None> $onLoad
     * @return Result<Render>
     */
    public function mount(string $fileName, false|callable $onLoad = false): Result;
    /**
     * Render a document.
     * 
     * # Warning
     * 
     * This method internally redirects the current buffer output.\
     * __Do not__ run async code inside `$fileName`!
     * @param  string                             $fileName   Name of the document.
     * @param  array<string|int,mixed>|Query|Body $properties Properties for the document.
     * @return ResponseModifier
     */
    public function render(string $fileName, array|Query|Body $properties = []):ResponseModifier;
}