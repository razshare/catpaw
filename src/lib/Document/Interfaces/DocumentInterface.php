<?php
namespace CatPaw\Document\Interfaces;

use CatPaw\Core\None;
use CatPaw\Core\Result;

interface DocumentInterface {
    /**
     * Mount a document or a directory of documents.
     * @param  string       $path
     * @return Result<None>
     */
    public function mount(string $path): Result;
    /**
     * Run a document.
     * 
     * # Warning
     * 
     * This method internally redirects the current buffer output.\
     * __Do not__ run async code inside `$document`!
     * @param  string              $document   Name of the document.\
     *                                         This can be the actual file name or the document 
     *                                         name you've assigned to the document using `name()`.
     * @param  array<string,mixed> $properties Properties for the document.
     * @return Result<string>
     */
    public function run(string $document, array $properties = []):Result;
}