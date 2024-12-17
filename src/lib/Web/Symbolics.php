<?php
namespace CatPaw\Web;

use function CatPaw\Core\error;

use function CatPaw\Core\ok;
use CatPaw\Core\Result;

readonly class Symbolics {
    /**
     * 
     * @param  string       $prefix
     * @param  string       $root
     * @param  string       $fileName
     * @return Result<self>
     */
    public static function fromRootAndPrefixAndFileName(
        string $prefix,
        string $root,
        string $fileName,
    ):Result {
        $offset = strpos($fileName, $root);
        $offset = $offset?:0;
        $path   = substr($fileName, $offset + strlen($root));


        $fileName = preg_replace('/\.php$/i', '', preg_replace('/\.\/+/', '/', '.'.DIRECTORY_SEPARATOR.$path));

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $fileName = preg_replace('/\\\\/', '/', $fileName);
        }

        
        if (!preg_match('/^(.*)(\.|\/)(.*)$/', $fileName, $matches)) {
            return error("Invalid symbolic path for file `$fileName`.");
        }

        $symbolicPath   = $prefix.$matches[1];
        $symbolicPath   = preg_replace(['/^\/+/','/\/index$/'], ['/',''], $symbolicPath);
        $symbolicMethod = strtoupper($matches[3]);

        return ok(
            new self(
                method: $symbolicMethod,
                path: $symbolicPath,
            )
        );
    }

    private function __construct(
        public string $method,
        public string $path,
    ) {
    }
}