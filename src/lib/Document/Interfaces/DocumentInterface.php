<?php
namespace CatPaw\Document\Interfaces;

use CatPaw\Core\Result;
use CatPaw\Document\Render;
use CatPaw\Web\Query;

interface DocumentInterface {
    /**
     * Mount a document by file name.
     * @param  string         $fileName
     * @return Result<Render>
     */
    public function mount(string $fileName): Result;
    /**
     * Render a document.
     * 
     * # Warning
     * 
     * This method internally redirects the current buffer output.\
     * __Do not__ run async code inside `$document`!
     * @param  string              $documentName Name of the document.\
     *                                           This can be the actual file name or the document 
     *                                           name you've assigned to the document using `name()`.
     * @param  array<string,mixed> $properties   Properties for the document.
     * @return Result<string>
     */
    public function render(string $documentName, array|Query $properties = []):Result;
}