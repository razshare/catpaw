<?php
namespace CatPaw\Document\Interfaces;

use CatPaw\Core\Result;
use CatPaw\Document\Render;
use CatPaw\Web\Interfaces\ResponseModifier;
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
     * __Do not__ run async code inside `$documentName`!
     * @param  string              $documentName Name of the document.
     * @param  array<string,mixed> $properties   Properties for the document.
     * @return ResponseModifier
     */
    public function render(string $documentName, array|Query $properties = []):ResponseModifier;
}