<?php
namespace CatPaw\Document;

readonly class MountContext {
    /**
     * 
     * @param  string                 $fileName
     * @param  array<string,callable> $functions
     * @param  array<string,mixed>    $variables
     * @param  array<string,mixed>    $constants
     * @param  false|callable         $mountFunction
     * @return void
     */
    public function __construct(
        public string $fileName,
        public array $functions,
        public array $variables,
        public array $constants,
        public mixed $mountFunction,
    ) {
    }
}