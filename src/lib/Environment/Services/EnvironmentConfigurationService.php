<?php
namespace CatPaw\Environment\Services;

use CatPaw\Attributes\Service;

#[Service]
class EnvironmentConfigurationService {
    /** @var array<string> */
    private array $eitherFileNames = [];

    /**
     * Set the allowed environment file names.
     * @param  array<string> $eitherFileName
     * @return void
     */
    public function setFileNames(string ...$eitherFileNames):void {
        $this->eitherFileNames = $eitherFileNames;
    }

    /**
     * Get the allowed environment file names.
     * @return array<string>
     */
    public function getFileNames():array {
        return $this->eitherFileNames;
    }
}