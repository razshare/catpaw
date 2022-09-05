<?php
namespace CatPaw\Environment\Services;

use CatPaw\Attributes\{File, Service};
#[Service]
class EnvironmentConfigurationService {
    /** @var array<File> */
    private array $files = [];

    /**
     * Set the allowed environment file names.
     * @param  array<string|File> $files
     * @return void
     */
    public function setFiles(string|File ...$files):void {
        $this->files = [];
        foreach ($files as $file) {
            if (is_string($file)) {
                $file = new File($file);
            }
            $this->files[] = $file;
        }
    }

    /**
     * Get the allowed environment files.
     * @return array<File>
     */
    public function getFiles():array {
        return $this->files;
    }
}