<?php
namespace CatPaw\Attributes;

use Attribute;

#[Attribute]
class File {
    /**
     * Create an environment file entry for your application.
     * @see Environment
     * @param string $fileName the file to load
     * @param bool   $external when true the environment service will first lookup the file 
     *                         outside the phar archive and then, if the file doesn't exist, 
     *                         will lookup the file inside the phar archive.
     * 
     * Obviously if you're not running the application inside a phar archive this 
     * parameter has no effect on your application.
     */
    public function __construct(
        private string $fileName,
        private bool $external = true,
    ) {
    }

    public function getFileName():string {
        return $this->fileName;
    }

    public function allowsExternal():bool {
        return $this->external;
    }
}