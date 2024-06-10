<?php
namespace CatPaw\Web;

use CatPaw\Core\File;
use CatPaw\Core\None;
use CatPaw\Core\Unsafe;

readonly class FormFile {
    public function __construct(
        public string $fileName,
        public string $fileContents,
    ) {
    }

    /**
     * Save the file with a name.
     * @param  string       $fileName The full name of the file where to save this data.
     * @return Unsafe<None>
     */
    public function saveAs(string $fileName):Unsafe {
        return File::writeFile($fileName, $this->fileContents);
    }
}